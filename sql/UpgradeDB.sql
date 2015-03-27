DROP PROCEDURE IF EXISTS AddForeignKeyIfNotExists;
DROP PROCEDURE IF EXISTS AddUniqueKeyIfNotExists;
DROP PROCEDURE IF EXISTS Add_ModifyColumn;

DELIMITER $$

CREATE PROCEDURE AddForeignKeyIfNotExists (IN TableName TEXT,IN ColumnName TEXT,IN ReferencedTableName TEXT,IN ReferencedColumnName TEXT)
  BEGIN
    SET @count = 0;
    SELECT COUNT(*) INTO @count FROM `information_schema`.`KEY_COLUMN_USAGE`
    WHERE `TABLE_NAME` = TableName AND `COLUMN_NAME` = ColumnName AND `REFERENCED_COLUMN_NAME` IS NOT NULL;
    IF @count = 0 THEN
      SET @stmt = CONCAT('ALTER TABLE `',TableName,'` ADD FOREIGN KEY (`',ColumnName,'`) REFERENCES `',ReferencedTableName,'`(`',ReferencedColumnName,'`)');
      PREPARE stmt FROM @stmt;
      EXECUTE stmt;
      DEALLOCATE PREPARE stmt;
    END IF;
  END $$

CREATE PROCEDURE AddUniqueKeyIfNotExists(IN TableName TEXT,IN ColumnName TEXT)
  BEGIN
    SET @count = 0;
    SELECT COUNT(*) INTO @count FROM `information_schema`.`KEY_COLUMN_USAGE`
    WHERE `TABLE_NAME` = TableName AND `COLUMN_NAME` = ColumnName AND `POSITION_IN_UNIQUE_CONSTRAINT` IS NOT NULL;
    IF @count = 0 THEN
      SET @stmt = CONCAT('ALTER TABLE `',TableName,'` ADD UNIQUE KEY(`',ColumnName,'`)');
      PREPARE stmt FROM @stmt;
      EXECUTE stmt;
      DEALLOCATE PREPARE stmt;
    END IF;
  END $$

CREATE PROCEDURE Add_ModifyColumn (IN TableName TEXT,IN ColumnName TEXT,IN Definition TEXT)
  BEGIN
    SET @count = 0;
    SELECT COUNT(*) INTO @count FROM `information_schema`.`STATISTICS`
    WHERE `TABLE_NAME` = TableName AND `COLUMN_NAME` = ColumnName;
    IF @count = 0 THEN
      SET @stmt = CONCAT('ALTER TABLE `',TableName,'` ADD COLUMN `',ColumnName,'` ',Definition);
      PREPARE stmt FROM @stmt;
      EXECUTE stmt;
      DEALLOCATE PREPARE stmt;
    END IF;
  END $$

DELIMITER ;

CREATE TABLE IF NOT EXISTS `UpgradeDB`(
  `ID` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `Type` ENUM('test','core') NOT NULL,
  `Version` CHAR(4),
  UNIQUE KEY(`Type`)
) ENGINE='InnoDB' DEFAULT CHARSET='utf8' COLLATE='utf8_general_ci';