# Pollaris

Pollaris is a polling tool to plan, organise and make decisions quickly, easily and without registration.

It is written with [Symfony](https://symfony.com/) and works with [PHP](https://www.php.net/) 8.2 or 8.3.
The only supported database for now is PostgreSQL >= 15.

Pollaris is licensed under [GNU Affero General Public License v3.0 or later](https://framagit.org/pollaris/pollaris/-/blob/main/LICENSE.txt).

## Documentation

### Setup the development environment

#### Setup Docker

The development environment is managed with Docker by default.

First, make sure to install [Docker Engine](https://docs.docker.com/engine/install/).
The `docker` command must be executable by your normal user.

#### Install Pollaris

Clone the repository:

```console
$ git clone https://framagit.org/pollaris/pollaris.git
```

Install the dependencies:

```console
$ make install
```

Start the development server:

```console
$ make docker-start
```

Setup the database:

```console
$ make db-setup
```

You should be able to open [localhost:8000](http://localhost:8000) and create your first poll.

#### Working in the Docker containers

There are few scripts to allow to execute commands in the Docker containers easily:

```console
$ ./docker/bin/php
$ ./docker/bin/composer
$ ./docker/bin/npm
$ ./docker/bin/psql
```

### Updating the development environment

Pull the changes with Git:

```console
$ git pull
```

If dependencies have been added or updated, install them:

```console
$ make install
```

Execute the migrations:

```console
$ make db-migrate
```

Sometimes, you may also have to rebuild the Docker image:

```console
$ make docker-build
```

Remember to restart the containers then.
