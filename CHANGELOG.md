# Changelog

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
