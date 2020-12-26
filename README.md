# exportPsqlToMySQL

dump  ( **export** ) PostgreSQL Database To MySQL Database with all rows 

# USE CASES

when you have a PostgreSQL database and you want to import all of its tables and rows into a MySQL database.<br>
this class will generate the Query String for you to paste it inside MySQL Query Tool or import it to the database directly

# HOW TO USE


1.clone the code into your system

```
  git clone https://github.com/kaz3m/exportPsqlToMySQL
```

2. PLACE YOUT DB CREDENTIALS INSIDE <b>exportPsqlToMySQL.class.php

```PHP
  $this->psql_username = '';
  $this->psql_password = '';
  $this->psql_database = '';
  $this->psql_host = '';
  $this->psql_port = '';
  // LEAVE ABOVE PARAMETERS EMPTY IF YOU ENTER `psql_uri`
  // ex: "dbname=DB_NAME host=HOST port=5432 user=USER_NAME password=PASS sslmode=require";
  $this->psql_uri = "";
```

 - 3.INIT THE CLASS

~~~
Terminal: php example.php > database_name.sql
Browser: http://localhost/exportPsqlToMySQL/example.php
~~~
