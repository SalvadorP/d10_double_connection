# d10_double_connection
Drupal 10 instance to test DDEV and also a double connection to double database, one is the replica from the other.

# How to install
1. Change if necessary the name of the project on .ddev
2. Run composer install and install all the D10 core and contrib and dependencies
3. Add to settings.php the connection to the replica, below the settings.ddev.php lines:
```
if (getenv('IS_DDEV_PROJECT') == 'true' && file_exists(__DIR__ . '/settings.ddev.php')) {
  include __DIR__ . '/settings.ddev.php';
}

$databases['default']['replica']['database'] = "db";
$databases['default']['replica']['username'] = "db";
$databases['default']['replica']['password'] = "db";
$databases['default']['replica']['host'] = "db-external";
$databases['default']['replica']['port'] = 3306;
$databases['default']['replica']['driver'] = "mysql";
```
## Restore the example DBs
You can create your own test DBs or restore the example DBs.
```
$ ddev ssh
$ unzip default_db.zip
$ mysql -h db -udb -pdb db < default_db.sql
$ unzip default_db.zip
$ mysql -h db-external -udb -pdb db < external_db.sql
```
## Create new DBs.
1. Install the site on the Default DB.
2. Then after the default DB is installed, overwrite the default/replica config so you can install the second DB.
```
$databases['default']['default']['host'] = "db-external";
```
3. After the installation has been completed, switch back the default/default entries to default/replica
```
$databases['default']['replica']['host'] = "db-external";
```
4. Create nodes on the default DB, and switch to replica when need to create nodes there.
5. On each change clear caches, and the DB will be switched seamlessly.

# How to test.
Once the two databases have data, the test can be performed using this endpoint:
**doubledb/list?replica=1**

<ins>It needs basic auth, just for testing purposes.</ins>

The parameter replica=1 means that the DB going to be used will be replica.
If no replica parameter or replica=0, the default database will be used.
