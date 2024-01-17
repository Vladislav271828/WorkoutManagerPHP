<?php

$mysqli = new mysqli("localhost:3306", "root", "root", "testforgym");


if ($mysqli->connect_error) {
  die("Connection failed: " . $mysqli->connect_error);
}

$mysqli->set_charset("utf8");