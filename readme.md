# Setup

* Install docker
* Follow post-install actions from https://docs.docker.com/engine/install/linux-postinstall/ (important, otherwise needs sudo all the time)
* Clone moodle docker from https://github.com/moodlehq/moodle-docker
* Clone moodle using `git clone https://github.com/moodle/moodle.git --branch MOODLE_311_STABLE --single-branch`
* Clone adviser
	* install requirements
* Set environment (just pack those into a .sh file and source it)
	* `export MOODLE_DOCKER_WWWROOT=/home/ubuntu/moodle`
	* `export MOODLE_DOCKER_DB=mysql`
	* `export MOODLE_DOCKER_DIR=/home/ubuntu/moodle-docker`
* Ensure customized config.php for the Docker containers is in place
  `cp $MOODLE_DOCKER_DIR/config.docker-template.php $MOODLE_DOCKER_WWWROOT/config.php`

# Run
1. adviser
	* `python run_server.py` runs server on port 44123
2. moodle
	1. `$MOODLE_DOCKER_DIR/bin/moodle-docker-compose up -d`
	2. `$MOODLE_DOCKER_DIR/bin/moodle-docker-wait-for-db`
3. Port forward adviser and moodle
	* `ssh -fNL 8000:localhost:8000 girlsday`
	* `ssh -fNL 44123:localhost:44123 girlsday`
4. Access via `http://localhost:8000`
5. Shutdown using `$MOODLE_DOCKER_DIR/bin/moodle-docker-compose down`

# Acces DB from inside docker

1. Connect to bash of mysql docker container (get name using `docker ps`)
	* `docker exec -it moodle-docker_db_1 mysql -u 'moodle' -p` (replace *docker_db_1* with mysql container name from `docker ps`)
	* Enter password `m@0dl3ing` (find in https://github.com/moodlehq/moodle-docker/blob/master/db.mysql.yml)
2. Access database
	* `USE moodle;` to open database
	* `SHOW TABLES;` to see all tables
		* Can also view scheme at https://www.examulator.com/er/output/tables/lesson_pages.html

# Access DB from outside docker
	* open `vim moodle-docker/base.yml`
	* in section `services` -> `db` add 
		* ```
			ports:
				- "3306:3306"
		 ```
	* now, can connect via bash or python, e.g.
	 `mysql --host=127.0.0.1 --port=3306 --protocol=tcp -u "moodle" -p`
	 	* password `m@0dl3ing`
