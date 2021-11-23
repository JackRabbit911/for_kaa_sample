<?php

namespace WN\User\Model;

class Sql
{
    public static $create_groups_sqlite = 
    "CREATE TABLE IF NOT EXISTS `groups` 
    (`id` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT, 
    `parent` INTEGER, `title` TEXT NOT NULL UNIQUE, `desc` TEXT)";

    public static $role = "WITH RECURSIVE tmp(id) AS (
        SELECT id FROM groups WHERE title = :group_id
     UNION
     SELECT groups.parent
     FROM groups, tmp
     WHERE groups.id = tmp.id
     ) 
     SELECT MAX(users_groups.role) AS role
     FROM groups, tmp
     LEFT JOIN users_groups ON users_groups.group_id = groups.id 
     WHERE groups.id = tmp.id AND users_groups.user_id = :user_id";

     public static $role_sqlite = "WITH RECURSIVE tmp(id) AS (
        SELECT id FROM `groups` WHERE `title` = :group_id
     UNION
     SELECT groups.parent
     FROM `groups`, `tmp`
     WHERE groups.id = tmp.id
     ) 
     SELECT MAX(users_groups.role) AS role
     FROM `groups`, `tmp`
     LEFT JOIN `users_groups` ON users_groups.group_id = groups.id 
     WHERE groups.id = tmp.id AND users_groups.user_id = :user_id";

     public static $role_mysql = "WITH RECURSIVE cte(id) AS (
         SELECT id FROM `groups` WHERE `title` = :group_id
      UNION ALL
      SELECT gr.parent
      FROM `groups` gr
      INNER JOIN cte ON gr.id = cte.id
      ) 
      SELECT * FROM cte";

     public static function get($key)
     {
        return static::$$key;
     }
}