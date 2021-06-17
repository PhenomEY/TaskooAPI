![Lines of code](https://img.shields.io/tokei/lines/github/TaskooApp/TaskooAPI)
# Taskoo - Easy task management API

<img width="250" src="https://media.taskoo.de/Logo_GREEN.svg">

## Installation
``` bash
# clone the newest version of TaskooAPI
$ git clone git@github.com:TaskooApp/TaskooAPI.git
$ cd TaskooAPI

# install TaskooAPI
$ composer install

# setup your DATABASE_URL in the .env file

# create your database
$ bin/console doctrine:database:create

# create migration
$ bin/console make:migration
$ bin/console doctrine:migrations:migrate

# load your default data through fixtures
$ bin/console doctrine:fixtures:load

# default admin account:
$ username: admin@taskoo.de
$ password: admin123
```

### Setting up the Taskoo Application
https://github.com/TaskooApp/Taskoo
### 