#!/bin/bash
# Install WordPress test environment
# Based on WordPress test setup

if [ $# -lt 3 ]; then
	echo "usage: $0 <db-name> <db-user> <db-pass> [db-host] [wp-version]"
	exit 1
fi

DB_NAME=$1
DB_USER=$2
DB_PASS=$3
DB_HOST=${4-localhost}
WP_VERSION=${5-latest}

WP_TESTS_DIR=${WP_TESTS_DIR-/tmp/wordpress-tests-lib}
WP_CORE_DIR=${WP_CORE_DIR-/tmp/wordpress}

set -ex

install_wp() {
	if [ -d $WP_CORE_DIR ]; then
		return;
	fi

	mkdir -p $WP_CORE_DIR

	if [ $WP_VERSION == 'latest' ]; then
		local ARCHIVE_NAME='latest'
	else
		local ARCHIVE_NAME="wordpress-$WP_VERSION"
	fi

	wget -nv -O /tmp/wordpress.tar.gz https://wordpress.org/${ARCHIVE_NAME}.tar.gz
	tar --strip-components=1 -zxmf /tmp/wordpress.tar.gz -C $WP_CORE_DIR

	wget -nv -O $WP_CORE_DIR/wp-config.php https://develop.svn.wordpress.org/trunk/wp-config-sample.php

	sed -i "s/youremptytestdbnamehere/$DB_NAME/" $WP_CORE_DIR/wp-config.php
	sed -i "s/yourusernamehere/$DB_USER/" $WP_CORE_DIR/wp-config.php
	sed -i "s/yourpasswordhere/$DB_PASS/" $WP_CORE_DIR/wp-config.php
	sed -i "s|localhost|${DB_HOST}|" $WP_CORE_DIR/wp-config.php
}

install_test_suite() {
	# set up testing suite if it doesn't exist
	if [ ! -d $WP_TESTS_DIR ]; then
		# set up testing suite
		mkdir -p $WP_TESTS_DIR
		svn co --quiet https://develop.svn.wordpress.org/trunk/tests/phpunit/includes/ $WP_TESTS_DIR/includes
	fi

	if [ ! -f $WP_TESTS_DIR/wp-tests-config.php ]; then
		download https://develop.svn.wordpress.org/trunk/wp-tests-config-sample.php $WP_TESTS_DIR/wp-tests-config.php
		sed -i "s/youremptytestdbnamehere/$DB_NAME/" $WP_TESTS_DIR/wp-tests-config.php
		sed -i "s/yourusernamehere/$DB_USER/" $WP_TESTS_DIR/wp-tests-config.php
		sed -i "s/yourpasswordhere/$DB_PASS/" $WP_TESTS_DIR/wp-tests-config.php
		sed -i "s|localhost|${DB_HOST}|" $WP_TESTS_DIR/wp-tests-config.php
	fi

}

install_wp
install_test_suite
