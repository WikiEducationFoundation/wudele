# Changelog

## 2025-04-16 - 0.4.0

### New

- Allow to customise the links to the polls ([78010c3](https://framagit.org/pollaris/pollaris/-/commit/78010c3))
- Allow to limit the number of "yes" answers ([57ceb96](https://framagit.org/pollaris/pollaris/-/commit/57ceb96))
- Allow to display the polls as tables ([510a027](https://framagit.org/pollaris/pollaris/-/commit/510a027))

### Improvements

- Move the vote on the poll page ([32e9a40](https://framagit.org/pollaris/pollaris/-/commit/32e9a40))
- Always display poll details and summary ([fa79228](https://framagit.org/pollaris/pollaris/-/commit/fa79228))
- Change the vote cursor by a pointer ([8e3b2ca](https://framagit.org/pollaris/pollaris/-/commit/8e3b2ca))
- Display count of preferred choices in bold ([ed71e8b](https://framagit.org/pollaris/pollaris/-/commit/ed71e8b))
- Improve the look of the vote icons ([0192af6](https://framagit.org/pollaris/pollaris/-/commit/0192af6))
- Increase contrast of "maybe" votes ([ef60fe7](https://framagit.org/pollaris/pollaris/-/commit/ef60fe7))

### Bug fixes

- Allow to vote without selecting options ([2430897](https://framagit.org/pollaris/pollaris/-/commit/2430897))

### Technical

- Update the dependencies ([1e1976f](https://framagit.org/pollaris/pollaris/-/commit/1e1976f), [a1d89d7](https://framagit.org/pollaris/pollaris/-/commit/a1d89d7), [9312639](https://framagit.org/pollaris/pollaris/-/commit/9312639), [43a26d3](https://framagit.org/pollaris/pollaris/-/commit/43a26d3), [fb9f0f5](https://framagit.org/pollaris/pollaris/-/commit/fb9f0f5), [d573ffb](https://framagit.org/pollaris/pollaris/-/commit/d573ffb), [33e86f9](https://framagit.org/pollaris/pollaris/-/commit/33e86f9), [f4f5436](https://framagit.org/pollaris/pollaris/-/commit/f4f5436), [15243c1](https://framagit.org/pollaris/pollaris/-/commit/15243c1), [2933866](https://framagit.org/pollaris/pollaris/-/commit/2933866))

### Developers

- Add a `.no-mobile` class ([05c9426](https://framagit.org/pollaris/pollaris/-/commit/05c9426))
- Move `.radio--vote` class to custom/votes.css ([a4f649d](https://framagit.org/pollaris/pollaris/-/commit/a4f649d))
- Extract a `proposals/_list` partial template ([bcdfc67](https://framagit.org/pollaris/pollaris/-/commit/bcdfc67))
- Fix running local tests ([fb8b5df](https://framagit.org/pollaris/pollaris/-/commit/fb8b5df))

## 2025-01-12 - 0.3.0

### New

- Allow to select dates with a calendar ([c874ec3](https://framagit.org/pollaris/pollaris/-/commit/c874ec3))
- Add a summary screen to the poll creation steps ([ad6fa64](https://framagit.org/pollaris/pollaris/-/commit/ad6fa64))

### Improvements

- Improve the design of the poll screen ([87d441f](https://framagit.org/pollaris/pollaris/-/commit/87d441f))
- Add a button to switch between simple/details vote views ([089b5cc](https://framagit.org/pollaris/pollaris/-/commit/089b5cc))
- Redesign the vote form ([762438b](https://framagit.org/pollaris/pollaris/-/commit/762438b))
- Improve the workflow after voting ([daadabd](https://framagit.org/pollaris/pollaris/-/commit/daadabd))
- Allow to edit poll's title and description ([5882bdc](https://framagit.org/pollaris/pollaris/-/commit/5882bdc))
- Block access to poll while creation is not complete ([1ba84b2](https://framagit.org/pollaris/pollaris/-/commit/1ba84b2))
- Replace the submit labels by "Next" ([d537836](https://framagit.org/pollaris/pollaris/-/commit/d537836))
- Add a "previous" buttons to the forms ([f58f6b1](https://framagit.org/pollaris/pollaris/-/commit/f58f6b1))
- Explain how the email is used in the interface ([24bd672](https://framagit.org/pollaris/pollaris/-/commit/24bd672))
- Increase the global border radius ([e6dc7c0](https://framagit.org/pollaris/pollaris/-/commit/e6dc7c0))
- Make "Apply same slot to all dates" more visible ([d2b802b](https://framagit.org/pollaris/pollaris/-/commit/d2b802b))
- Validate dates are not empty in form ([20211f3](https://framagit.org/pollaris/pollaris/-/commit/20211f3))

### Developers

- Add a notification component ([172c4ae](https://framagit.org/pollaris/pollaris/-/commit/172c4ae))
- Add a copy-to-clipboard Stimulus controller ([6459a1a](https://framagit.org/pollaris/pollaris/-/commit/6459a1a))
- Add more icons ([d1a2a61](https://framagit.org/pollaris/pollaris/-/commit/d1a2a61), [799509b](https://framagit.org/pollaris/pollaris/-/commit/799509b), [c2b1f4c](https://framagit.org/pollaris/pollaris/-/commit/c2b1f4c))
- Add a warning color to CSS ([d12fe12](https://framagit.org/pollaris/pollaris/-/commit/d12fe12))
- Generate fieldsets for compound form rows ([e082e1a](https://framagit.org/pollaris/pollaris/-/commit/e082e1a))
- Add CSS for the "vote" radio buttons ([5028201](https://framagit.org/pollaris/pollaris/-/commit/5028201))
- Remove padding/margin on fieldsets in fieldset ([2ec3a4b](https://framagit.org/pollaris/pollaris/-/commit/2ec3a4b))
- Add a text-block CSS class ([cc6b0a5](https://framagit.org/pollaris/pollaris/-/commit/cc6b0a5))
- Add CSS classes for panels ([0014002](https://framagit.org/pollaris/pollaris/-/commit/0014002))
- Add style to h4 titles ([0887839](https://framagit.org/pollaris/pollaris/-/commit/0887839))
- Change style of h3 titles ([491bb07](https://framagit.org/pollaris/pollaris/-/commit/491bb07))
- Add a CSS text--success class ([ca94254](https://framagit.org/pollaris/pollaris/-/commit/ca94254))
- Add a separator to the panel component ([24e72e1](https://framagit.org/pollaris/pollaris/-/commit/24e72e1))
- Improve style of dl lists ([b6f6796](https://framagit.org/pollaris/pollaris/-/commit/b6f6796))
- Fix icons to better adapt to adjacent text ([fc57375](https://framagit.org/pollaris/pollaris/-/commit/fc57375))
- Add style for a "success" button ([e4ce252](https://framagit.org/pollaris/pollaris/-/commit/e4ce252))
- Add functions to Stimulus "collection" controller ([0a325d8](https://framagit.org/pollaris/pollaris/-/commit/0a325d8))
- Add a CSS class text--secondary ([9e9fb97](https://framagit.org/pollaris/pollaris/-/commit/9e9fb97))
- Allow to rotate the icons ([63c1e9f](https://framagit.org/pollaris/pollaris/-/commit/63c1e9f))
- Refactor the handling of poll creation process ([0f4ee8d](https://framagit.org/pollaris/pollaris/-/commit/0f4ee8d))
- Set the look of "help" messages in forms ([ea69b4f](https://framagit.org/pollaris/pollaris/-/commit/ea69b4f))
- Update the copyright year ([6abf59a](https://framagit.org/pollaris/pollaris/-/commit/6abf59a))

## 2024-11-01 - 0.2.0

### New

- Add support for date polls ([6c2f670](https://framagit.org/pollaris/pollaris/-/commit/6c2f670), [3f7b5b9](https://framagit.org/pollaris/pollaris/-/commit/3f7b5b9))
- Allow to edit a vote ([6cc0a1f](https://framagit.org/pollaris/pollaris/-/commit/6cc0a1f))

### Improvements

- Display the preferred proposals ([d3a67c3](https://framagit.org/pollaris/pollaris/-/commit/d3a67c3), [e3ff053](https://framagit.org/pollaris/pollaris/-/commit/e3ff053), [d41e14a](https://framagit.org/pollaris/pollaris/-/commit/d41e14a), [2bafede](https://framagit.org/pollaris/pollaris/-/commit/2bafede))
- Add primary color to the main titles (`h1`) ([f19e5c0](https://framagit.org/pollaris/pollaris/-/commit/f19e5c0))
- Increase the size of the radio buttons ([50c07f8](https://framagit.org/pollaris/pollaris/-/commit/50c07f8))
- Improve the design of proposals buttons ([12ad303](https://framagit.org/pollaris/pollaris/-/commit/12ad303))

### Bug fixes

- Use fieldsets in votes form ([4719b48](https://framagit.org/pollaris/pollaris/-/commit/4719b48))
- Add missing errors in polls ([aeae910](https://framagit.org/pollaris/pollaris/-/commit/aeae910))
- Make assets URLs absolute ([1539d7e](https://framagit.org/pollaris/pollaris/-/commit/1539d7e))
- Check that poll is created before voting ([055f003](https://framagit.org/pollaris/pollaris/-/commit/055f003))
- Put new vote "cancel" and "submit" buttons on same line ([d6ee85a](https://framagit.org/pollaris/pollaris/-/commit/d6ee85a))
- Fix initialization of Answer ([702a67a](https://framagit.org/pollaris/pollaris/-/commit/702a67a))

### Documentation

- Setup the full documentation ([2a1a068](https://framagit.org/pollaris/pollaris/-/commit/2a1a068))

### Technical

- Make sure to require PostgreSQL >= 15 everywhere ([ce32379](https://framagit.org/pollaris/pollaris/-/commit/ce32379))
- Update the dependencies ([a91b478](https://framagit.org/pollaris/pollaris/-/commit/a91b478), [8cd582f](https://framagit.org/pollaris/pollaris/-/commit/8cd582f), [d6c7ba6](https://framagit.org/pollaris/pollaris/-/commit/d6c7ba6))

### Developers

- Provide a modal system ([cd9497d](https://framagit.org/pollaris/pollaris/-/commit/cd9497d))
- Provide the icons system ([a192bbb](https://framagit.org/pollaris/pollaris/-/commit/a192bbb))
- Provide tests ([6630c52](https://framagit.org/pollaris/pollaris/-/commit/6630c52), [2107fb3](https://framagit.org/pollaris/pollaris/-/commit/2107fb3), [6ddb1e7](https://framagit.org/pollaris/pollaris/-/commit/6ddb1e7), [552e2c6](https://framagit.org/pollaris/pollaris/-/commit/552e2c6), [1263d9e](https://framagit.org/pollaris/pollaris/-/commit/1263d9e))
- Replace Parcel by esbuild ([49100a8](https://framagit.org/pollaris/pollaris/-/commit/49100a8))
- Configure GitLab CI ([f95db57](https://framagit.org/pollaris/pollaris/-/commit/f95db57))
- Add a merge request template ([86d18d5](https://framagit.org/pollaris/pollaris/-/commit/86d18d5))
- Improve the make commands ([9f2078d](https://framagit.org/pollaris/pollaris/-/commit/9f2078d), [c356474](https://framagit.org/pollaris/pollaris/-/commit/c356474))
- Declare Docker Compose project name in docker-compose.yml ([037d6f0](https://framagit.org/pollaris/pollaris/-/commit/037d6f0))
- Declare an `app.public_directory` parameter in config ([ca8be30](https://framagit.org/pollaris/pollaris/-/commit/ca8be30))
- Fix the warnings of PHPStan ([5389397](https://framagit.org/pollaris/pollaris/-/commit/5389397))
- Change ProposalForm into ProposalType ([0db291e](https://framagit.org/pollaris/pollaris/-/commit/0db291e))
- Configure Rector with `RectorConfig::configure()` ([57be612](https://framagit.org/pollaris/pollaris/-/commit/57be612))

## 2024-08-23 - 0.1.0

### New

- Allow to create polls ([e4f2784](https://framagit.org/pollaris/pollaris/-/commit/e4f2784))
- Allow to add proposals to polls ([6b4ac05](https://framagit.org/pollaris/pollaris/-/commit/6b4ac05))
- Allow to fill the identity of a poll's author ([ca49412](https://framagit.org/pollaris/pollaris/-/commit/ca49412))
- Allow to answer to a poll ([2c08c8d](https://framagit.org/pollaris/pollaris/-/commit/2c08c8d))
- Display the results of a poll ([ff6dec1](https://framagit.org/pollaris/pollaris/-/commit/ff6dec1))
