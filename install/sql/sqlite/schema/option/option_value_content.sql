DROP TABLE IF EXISTS `option_value_content`;

CREATE TABLE `option_value_content` (
  `option_value_id` INT NOT NULL,
  `language_id` INT NOT NULL,
  `option_id` INT NOT NULL,
  `name` TEXT NOT NULL,
  PRIMARY KEY (`option_value_id`,`language_id`)
);