<?php

// This file is part of Pollaris.
// Copyright 2024-2025 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace App\Entity;

use App\ActivityMonitor;
use App\Doctrine;
use App\Repository;
use App\Utils;
use Doctrine\Common\Collections;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: Repository\PollRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(
    fields: 'slug',
    message: new TranslatableMessage('poll.slug.already_used', domain: 'validators'),
)]
class Poll implements ActivityMonitor\TrackableEntityInterface
{
    public const MAX_TITLE_LENGTH = 200;
    public const MAX_AUTHOR_NAME_LENGTH = 100;
    public const MAX_SLUG_LENGTH = 20;
    public const SLUG_PATTERN = '/^[\w\-]+$/';

    public const TYPES = ['date', 'classic'];
    public const DEFAULT_TYPE = 'classic';

    #[ORM\Id]
    #[ORM\Column(length: 20)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: Doctrine\HexIdGenerator::class)]
    private ?string $id = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    #[ORM\Column(length: 20, unique: true, nullable: true)]
    #[Assert\Length(
        max: self::MAX_TITLE_LENGTH,
        maxMessage: new TranslatableMessage('poll.slug.max_length', domain: 'validators'),
    )]
    #[Assert\Regex(
        pattern: self::SLUG_PATTERN,
        message: new TranslatableMessage('poll.slug.pattern', domain: 'validators'),
    )]
    private ?string $slug = null;

    #[ORM\Column(length: 20)]
    private ?string $adminToken = null;

    #[ORM\Column(length: 20, options: ['default' => self::DEFAULT_TYPE])]
    #[Assert\Choice(
        choices: self::TYPES,
        message: new TranslatableMessage('poll.type.invalid', domain: 'validators'),
    )]
    private ?string $type = null;

    #[ORM\Column(length: self::MAX_TITLE_LENGTH)]
    #[Assert\NotBlank(
        message: new TranslatableMessage('poll.title.required', domain: 'validators'),
    )]
    #[Assert\Length(
        max: self::MAX_TITLE_LENGTH,
        maxMessage: new TranslatableMessage('poll.title.max_length', domain: 'validators'),
    )]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column(length: 255, options: ['default' => ''])]
    private ?string $password = null;

    #[ORM\Column(options: ['default' => false])]
    private ?bool $isPasswordForVotesOnly = null;

    #[ORM\Column(length: self::MAX_AUTHOR_NAME_LENGTH)]
    #[Assert\Length(
        max: self::MAX_AUTHOR_NAME_LENGTH,
        maxMessage: new TranslatableMessage('poll.author_name.max_length', domain: 'validators'),
    )]
    private string $authorName = '';

    #[ORM\Column(length: 255)]
    #[Assert\Email(
        message: new TranslatableMessage('poll.author_email.invalid', domain: 'validators'),
    )]
    private ?string $authorEmail = null;

    /** @var Collections\Collection<int, Proposal> */
    #[ORM\OneToMany(
        targetEntity: Proposal::class,
        mappedBy: 'poll',
        cascade: ['persist'],
        orphanRemoval: true,
    )]
    #[Assert\Valid]
    private Collections\Collection $proposals;

    /**
     * @var Collections\Collection<int, Date>
     */
    #[ORM\OneToMany(
        targetEntity: Date::class,
        mappedBy: 'poll',
        cascade: ['persist'],
        orphanRemoval: true,
    )]
    #[ORM\OrderBy(['value' => 'ASC'])]
    #[Assert\Valid]
    private Collections\Collection $dates;

    /** @var Collections\Collection<int, Vote> */
    #[ORM\OneToMany(
        targetEntity: Vote::class,
        mappedBy: 'poll',
        orphanRemoval: true,
    )]
    #[ORM\OrderBy(['createdAt' => 'ASC'])]
    private Collections\Collection $votes;

    #[ORM\Column(nullable: true)]
    private ?int $maxVotes = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $notifyOnVotes = false;

    /**
     * @var Collections\Collection<int, Comment>
     */
    #[ORM\OneToMany(
        targetEntity: Comment::class,
        mappedBy: 'poll',
        orphanRemoval: true,
    )]
    #[ORM\OrderBy(['createdAt' => 'ASC'])]
    private Collections\Collection $comments;

    #[ORM\Column(options: ['default' => false])]
    private bool $notifyOnComments = false;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $closedAt = null;

    public function __construct()
    {
        $this->type = self::DEFAULT_TYPE;
        $this->title = '';
        $this->description = '';
        $this->closedAt = Utils\Time::fromNow(1, 'month');
        $this->password = '';
        $this->isPasswordForVotesOnly = false;
        $this->authorName = '';
        $this->authorEmail = '';
        $this->notifyOnVotes = true;
        $this->notifyOnComments = true;
        $this->proposals = new Collections\ArrayCollection();
        $this->votes = new Collections\ArrayCollection();
        $this->dates = new Collections\ArrayCollection();
        $this->comments = new Collections\ArrayCollection();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function isCompleted(): bool
    {
        return $this->completedAt !== null;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeImmutable $completedAt): static
    {
        $this->completedAt = $completedAt;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function isClassicPoll(): bool
    {
        return $this->type === 'classic';
    }

    public function isDatePoll(): bool
    {
        return $this->type === 'date';
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function isTitleSet(): bool
    {
        return $this->title !== null && $this->title !== '';
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function isPasswordProtected(): bool
    {
        return $this->password !== '';
    }

    public function isVotePasswordProtected(): bool
    {
        return $this->isPasswordProtected() && $this->isPasswordForVotesOnly();
    }

    public function isFullPasswordProtected(): bool
    {
        return $this->isPasswordProtected() && !$this->isPasswordForVotesOnly();
    }

    public function isPasswordForVotesOnly(): ?bool
    {
        return $this->isPasswordForVotesOnly;
    }

    public function setIsPasswordForVotesOnly(bool $isPasswordForVotesOnly): static
    {
        $this->isPasswordForVotesOnly = $isPasswordForVotesOnly;

        return $this;
    }

    public function getAuthorName(): string
    {
        return $this->authorName;
    }

    public function setAuthorName(string $authorName): static
    {
        $this->authorName = $authorName;

        return $this;
    }

    public function getAuthorEmail(): ?string
    {
        return $this->authorEmail;
    }

    public function setAuthorEmail(string $authorEmail): static
    {
        $this->authorEmail = $authorEmail;

        return $this;
    }

    /**
     * @return Collections\Collection<int, Proposal>
     */
    public function getProposals(): Collections\Collection
    {
        return $this->proposals;
    }

    /**
     * @return Proposal[]
     */
    public function getPreferredChoices(): array
    {
        $maxYes = 0;
        $preferredChoices = [];

        foreach ($this->proposals as $proposal) {
            $countYes = $proposal->countAnswers('yes');

            if ($countYes === 0) {
                continue;
            }

            if ($countYes > $maxYes) {
                $maxYes = $countYes;
                $preferredChoices = [$proposal];
            } elseif ($countYes === $maxYes) {
                $preferredChoices[] = $proposal;
            }
        }

        return $preferredChoices;
    }

    public function getMaxCount(): int
    {
        $maxCount = 0;

        foreach ($this->proposals as $proposal) {
            $countYes = $proposal->countAnswers('yes');
            if ($countYes > $maxCount) {
                $maxCount = $countYes;
            }

            $countMaybe = $proposal->countAnswers('maybe');
            if ($countMaybe > $maxCount) {
                $maxCount = $countMaybe;
            }

            $countNo = $proposal->countAnswers('no');
            if ($countNo > $maxCount) {
                $maxCount = $countNo;
            }
        }

        return $maxCount;
    }
    public function addProposal(Proposal $proposal): static
    {
        if (!$this->proposals->contains($proposal)) {
            $this->proposals->add($proposal);
            $proposal->setPoll($this);
        }

        return $this;
    }

    public function removeProposal(Proposal $proposal): static
    {
        if ($this->proposals->removeElement($proposal)) {
            if ($proposal->getPoll() === $this) {
                $proposal->setPoll(null);
            }
        }

        return $this;
    }

    /**
     * @return Collections\Collection<int, Date>
     */
    public function getDates(): Collections\Collection
    {
        return $this->dates;
    }

    public function addDate(Date $date): static
    {
        if (!$this->dates->contains($date)) {
            $this->dates->add($date);
            $date->setPoll($this);
        }

        return $this;
    }

    public function removeDate(Date $date): static
    {
        if ($this->dates->removeElement($date)) {
            if ($date->getPoll() === $this) {
                $date->setPoll(null);
            }
        }

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    #[ORM\PostPersist]
    public function setDefaultSlug(PostPersistEventArgs $eventArgs): void
    {
        if (!$this->slug) {
            $this->setSlug($this->getId());
            $eventArgs->getObjectManager()->flush();
        }
    }

    public function isSlugCustomized(): bool
    {
        return $this->slug !== $this->id;
    }

    public function getAdminToken(): ?string
    {
        return $this->adminToken;
    }

    #[ORM\PrePersist]
    public function setAdminToken(): void
    {
        $this->adminToken = Utils\Random::hex(20);
    }

    /**
     * @return Collections\Collection<int, Vote>
     */
    public function getVotes(): Collections\Collection
    {
        return $this->votes;
    }

    public function countVotes(): int
    {
        return $this->votes->count();
    }

    public function addVote(Vote $vote): static
    {
        if (!$this->votes->contains($vote)) {
            $this->votes->add($vote);
            $vote->setPoll($this);
        }

        return $this;
    }

    public function removeVote(Vote $vote): static
    {
        if ($this->votes->removeElement($vote)) {
            if ($vote->getPoll() === $this) {
                $vote->setPoll(null);
            }
        }

        return $this;
    }

    public function getMaxVotes(): ?int
    {
        return $this->maxVotes;
    }

    public function setMaxVotes(?int $maxVotes): static
    {
        $this->maxVotes = $maxVotes;

        return $this;
    }

    public function isNotifyOnVotes(): bool
    {
        return $this->notifyOnVotes;
    }

    public function setNotifyOnVotes(bool $notifyOnVotes): static
    {
        $this->notifyOnVotes = $notifyOnVotes;

        return $this;
    }

    /**
     * @return Collections\Collection<int, Comment>
     */
    public function getComments(): Collections\Collection
    {
        return $this->comments;
    }

    public function countComments(): int
    {
        return $this->comments->count();
    }

    public function addComment(Comment $comment): static
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setPoll($this);
        }

        return $this;
    }

    public function removeComment(Comment $comment): static
    {
        if ($this->comments->removeElement($comment)) {
            if ($comment->getPoll() === $this) {
                $comment->setPoll(null);
            }
        }

        return $this;
    }

    public function isNotifyOnComments(): ?bool
    {
        return $this->notifyOnComments;
    }

    public function setNotifyOnComments(bool $notifyOnComments): static
    {
        $this->notifyOnComments = $notifyOnComments;

        return $this;
    }

    public function isClosed(): bool
    {
        if (!$this->closedAt) {
            return false;
        }

        return Utils\Time::relative('today') > $this->closedAt->modify('today');
    }

    public function getClosedAt(): ?\DateTimeImmutable
    {
        return $this->closedAt;
    }

    public function setClosedAt(?\DateTimeImmutable $closedAt): static
    {
        $this->closedAt = $closedAt;

        return $this;
    }
}
