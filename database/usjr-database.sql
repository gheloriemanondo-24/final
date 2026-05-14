DROP SCHEMA IF EXISTS `usjr`;

CREATE SCHEMA IF NOT EXISTS `usjr` DEFAULT CHARACTER SET utf8;

USE `usjr`;

DROP TABLE IF EXISTS `usjr`.`students`;
DROP TABLE IF EXISTS `usjr`.`programs`;
DROP TABLE IF EXISTS `usjr`.`departments`;
DROP TABLE IF EXISTS `usjr`.`colleges`;

CREATE TABLE `usjr`.`colleges` (
  `collid` INT NOT NULL,
  `collfullname` VARCHAR(100) NOT NULL,
  `collshortname` VARCHAR(20) NOT NULL,
  PRIMARY KEY (`collid`));

CREATE TABLE `usjr`.`departments`(
  `deptid` INT NOT NULL,
  `deptfullname` VARCHAR(100) NOT NULL,
  `deptshortname` VARCHAR(20),
  `deptcollid` INT NOT NULL, 
  PRIMARY KEY (`deptid`),
  CONSTRAINT `fk_department_college_id`
     FOREIGN KEY (`deptcollid`) 
     REFERENCES `usjr`.`colleges` (`collid`)
     ON DELETE NO ACTION
     ON UPDATE NO ACTION
  );

CREATE TABLE `usjr`.`programs` (
  `progid` INT NOT NULL,
  `progfullname` VARCHAR(100) NOT NULL,
  `progshortname` VARCHAR(20),
  `progcollid` INT NOT NULL,
  `progcolldeptid` INT NOT NULL,
  PRIMARY KEY (`progid`),
  CONSTRAINT `fk_program_college_id`
     FOREIGN KEY (`progcollid`)
     REFERENCES `usjr`.`colleges` (`collid`)
     ON DELETE NO ACTION
     ON UPDATE NO ACTION,
  CONSTRAINT `fk_program_college_department_id`
     FOREIGN KEY (`progcolldeptid`)
     REFERENCES `usjr`.`departments` (`deptid`)
     ON DELETE NO ACTION
     ON UPDATE NO ACTION
);

CREATE TABLE `usjr`.`students` (
  `studid` INT NOT NULL,
  `studfirstname` VARCHAR(50) NOT NULL,
  `studlastname` VARCHAR(50) NOT NULL,
  `studmidname` VARCHAR(50) NULL,
  `studcollid` INT NOT NULL,
  `studcolldeptid` INT NOT NULL,
  `studprogid` INT NOT NULL,
  `studyear` INT NOT NULL,
  PRIMARY KEY (`studid`),
  CONSTRAINT `fk_student_college_id`
     FOREIGN KEY (`studcollid`)
     REFERENCES `usjr`.`colleges` (`collid`)
     ON DELETE NO ACTION
     ON UPDATE NO ACTION,
  CONSTRAINT `fk_student_college_department_id`
     FOREIGN KEY (`studcolldeptid`)
     REFERENCES `usjr`.`departments` (`deptid`)
     ON DELETE NO ACTION
     ON UPDATE NO ACTION
);

INSERT INTO `usjr`.`users` (username, password) VALUES ('admin', 'admin');

INSERT INTO `usjr`.`colleges` VALUES (3,'School of Business and Management','SBM');
INSERT INTO `usjr`.`colleges` VALUES (6,'School of Arts and Sciences','SAS');
INSERT INTO `usjr`.`colleges` VALUES (4,'School of Engineering','SoENG');
INSERT INTO `usjr`.`colleges` VALUES (5,'School of Education','SED');
INSERT INTO `usjr`.`colleges` VALUES (11,'School of Computer Studies','SCS');
INSERT INTO `usjr`.`colleges` VALUES (20,'School of Allied Medical Sciences','SAMS');

INSERT INTO `usjr`.`departments` VALUES(3001,'Accountancy and Finance Department','',3);
INSERT INTO `usjr`.`departments` VALUES(3002,'Business and Entrepreneurship Department','',3);
INSERT INTO `usjr`.`departments` VALUES(3003,'Marketing and Human Resource Management Department','',3);
INSERT INTO `usjr`.`departments` VALUES(3004,'Tourism and Hospitality Management Department','THMD',3);

INSERT INTO `usjr`.`departments` VALUES(6001,'Department of Communications, Language and Literature','DLL',6);
INSERT INTO `usjr`.`departments` VALUES(6002,'Department of Mathematics and Sciences','DMS',6);
INSERT INTO `usjr`.`departments` VALUES(6003,'Department of Social Sciences and Philiosophy','DSSP',6);
INSERT INTO `usjr`.`departments` VALUES(6004,'Department of Psychology and Library Information Science','DPLIS',6);

INSERT INTO `usjr`.`departments` VALUES(4001,'Department of Civil Engineering','',4);
INSERT INTO `usjr`.`departments` VALUES(4002,'Department of Computer Engineering','',4);
INSERT INTO `usjr`.`departments` VALUES(4003,'Department of Electronics and Communications Engineering','',4);
INSERT INTO `usjr`.`departments` VALUES(4004,'Department of Electrical Engineering','',4);
INSERT INTO `usjr`.`departments` VALUES(4005,'Department of Industrial Enginering','',4);
INSERT INTO `usjr`.`departments` VALUES(4006,'Department of Mechanical Engineering','',4);

INSERT INTO `usjr`.`departments` VALUES(5001,'Department of Teacher Education','',5);
INSERT INTO `usjr`.`departments` VALUES(5002,'Department of Physical Education','',5);
INSERT INTO `usjr`.`departments` VALUES(5003,'Department of Special Education','',5);

INSERT INTO `usjr`.`departments` VALUES(11001,'CS/IT Department','',11);

INSERT INTO `usjr`.`departments` VALUES(20001,'Department of Nursing','',20);

INSERT INTO `usjr`.`programs` VALUES(33001001,'Bachelor of Science in Accountancy','BSA',3,3001);
INSERT INTO `usjr`.`programs` VALUES(33001002,'Bachelor of Science in Management Accounting','BSMA',3,3001);
INSERT INTO `usjr`.`programs` VALUES(33001003,'Bachelor of Science in Business Administration Major in Finanacial Management','BSBA-FM',3,3001);

INSERT INTO `usjr`.`programs` VALUES(33002001,'Bachelor of Science in Entrepreneurship','BS-Entrepreneurship',3,3002);

INSERT INTO `usjr`.`programs` VALUES(33003001,'Bachelor of Science in Business Administration Major in Operation Management','BSBA-OM',3,3003);
INSERT INTO `usjr`.`programs` VALUES(33003002,'Bachelor of Science in Business Administration Major in Human Resource Development Management','BSBA-HRDM',3,3003);
INSERT INTO `usjr`.`programs` VALUES(33003003,'Bachelor of Science in Business Administration Major in Marketing Management','BSBA-MM',3,3003);

INSERT INTO `usjr`.`programs` VALUES(33004001,'Bachelor of Science in Hospitality Management','BSHM',3,3004);
INSERT INTO `usjr`.`programs` VALUES(33004002,'Bachelor of Science in Hospitality Management Major in Food and Beverage','BSHM-FB',3,3004);
INSERT INTO `usjr`.`programs` VALUES(33004003,'Associate in Hospitality Management','AHM',3,3004);
INSERT INTO `usjr`.`programs` VALUES(33004004,'Associate in Tourism','ATourism',3,3004);

INSERT INTO `usjr`.`programs` VALUES(66001001,'Bachelor of Arts in Communication','BAComm',6,6001);
INSERT INTO `usjr`.`programs` VALUES(66001002,'Bachelor of Arts in English Language Studies','BAELS',6,6001);
INSERT INTO `usjr`.`programs` VALUES(66001003,'Bachelor of Arts in Journalism','BAJournalism',6,6001);
INSERT INTO `usjr`.`programs` VALUES(66001004,'Bachelor of Arts in Marketing Communication','BAMarComm',6,6001);

INSERT INTO `usjr`.`programs` VALUES(66002001,'Bachelor of Science in Biology Major in Medical Biology','BSBio-MB',6,6002);
INSERT INTO `usjr`.`programs` VALUES(66002002,'Bachelor of Science in Biology Major in Microbiology','BSBio-Microbio',6,6002);

INSERT INTO `usjr`.`programs` VALUES(66003001,'Bachelor of Arts in Political Science','BAPolSci',6,6003);
INSERT INTO `usjr`.`programs` VALUES(66003002,'Bachelor of Arts in International Studies','BAIS',6,6003);
INSERT INTO `usjr`.`programs` VALUES(66003003,'Bachelor of Arts in Philosophy','BAPhilo',6,6003);

INSERT INTO `usjr`.`programs` VALUES(66004001,'Bachelor of Science in Psychology','BSPsych',6,6004);

INSERT INTO `usjr`.`programs` VALUES(44001001,'Bachelor of Science in Civil Engineering','BSCE',4,4001);

INSERT INTO `usjr`.`programs` VALUES(44002001,'Bachelor of Science in Computer Engineering','BSCpE',4,4002);

INSERT INTO `usjr`.`programs` VALUES(44003001,'Bachelor of Science in Electronics and Communications Engineering','BSECE',4,4003);

INSERT INTO `usjr`.`programs` VALUES(44004001,'Bachelor of Science in Electrical Engineering','BSEE',4,4004);

INSERT INTO `usjr`.`programs` VALUES(44005001,'Bachelor of Science in Industrial Engineering','BSIE',4,4005);

INSERT INTO `usjr`.`programs` VALUES(44006001,'Bachelor of Science in Mechanical Engineering','BSME',4,4006);

INSERT INTO `usjr`.`programs` VALUES(55001001,'Bachelor of Elementary Education','BEEEd',5,5001);
INSERT INTO `usjr`.`programs` VALUES(55001002,'Bachelor of Early Childhood Education','BECE',5,5001);
INSERT INTO `usjr`.`programs` VALUES(55001003,'Bachelor of Secondary Education Major in English','BSEd-English',5,5001);
INSERT INTO `usjr`.`programs` VALUES(55001004,'Bachelor of Secondary Education Major in Filipino','BSEd-Filipino',5,5001);
INSERT INTO `usjr`.`programs` VALUES(55001005,'Bachelor of Secondary Education Major in Mathematics','BSEd-Mathematics',5,5001);
INSERT INTO `usjr`.`programs` VALUES(55001006,'Bachelor of Secondary Education Major in Science','BSEd-Science',5,5001);


INSERT INTO `usjr`.`programs` VALUES(55002001,'Bachelor of Physical Education','BPE',5,5002);

INSERT INTO `usjr`.`programs` VALUES(55003001,'Bachelor of Special Needs Education - Generalist','BSNE-General',5,5003);
INSERT INTO `usjr`.`programs` VALUES(55003002,'Bachelor of Special Needs Education Major in Early Childhood Education','BSNE-ECE',5,5003);
INSERT INTO `usjr`.`programs` VALUES(55003003,'Bachelor of Special Needs Education Major in Elementary School Teaching','BSNE-EST',5,5003);

INSERT INTO `usjr`.`programs` VALUES(1111001001,'Bachelor of Science in Computer Science','BSCS',11,11001);
INSERT INTO `usjr`.`programs` VALUES(1111001002,'Bachelor of Science in Information Technology','BSIT',11,11001);
INSERT INTO `usjr`.`programs` VALUES(1111001003,'Bachelor of Science in Information Systems','BSIS',11,11001);
INSERT INTO `usjr`.`programs` VALUES(1111001004,'Bachelor of Science in Entertainment and Multimedia Computing','BSEMC',11,11001);

INSERT INTO `usjr`.`programs` VALUES(2020001001,'Bachelof of Science in Nursing','BSN',20,20001);