/*
SQLyog Ultimate v11.42 (64 bit)
MySQL - 5.6.20-log : Database - ptr_portfolio
*********************************************************************
*/

/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
/*Table structure for table `blog_posts` */

DROP TABLE IF EXISTS `blog_posts`;

CREATE TABLE `blog_posts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `post_title` varchar(128) DEFAULT NULL,
  `post_content` text,
  `posted_by` int(10) DEFAULT NULL,
  `timestamp` int(11) DEFAULT NULL,
  `thumbnail` varchar(64) DEFAULT 'post-1.jpg',
  `thumbnail_small` varchar(64) DEFAULT '95x95-1.jpg' COMMENT '95x95',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;

/*Table structure for table `blog_slider` */

DROP TABLE IF EXISTS `blog_slider`;

CREATE TABLE `blog_slider` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slider_img` varchar(64) DEFAULT NULL,
  `description` varchar(64) DEFAULT NULL,
  `blog_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

/*Table structure for table `project_features` */

DROP TABLE IF EXISTS `project_features`;

CREATE TABLE `project_features` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `feature_title` varchar(64) DEFAULT NULL,
  `feature_icon` varchar(64) NOT NULL DEFAULT 'icon-ok-sign',
  `project_id` int(10) DEFAULT NULL,
  `link` varchar(64) NOT NULL DEFAULT '#',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=latin1;

/*Table structure for table `project_slider` */

DROP TABLE IF EXISTS `project_slider`;

CREATE TABLE `project_slider` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slider_name` varchar(64) DEFAULT NULL,
  `slider_description` text,
  `project_id` int(11) DEFAULT NULL,
  `thumbnail` varchar(64) DEFAULT NULL,
  `thumbnail_zoom` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=latin1;

/*Table structure for table `projects` */

DROP TABLE IF EXISTS `projects`;

CREATE TABLE `projects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `naam` varchar(64) DEFAULT NULL,
  `description` text,
  `thumbnail` varchar(64) DEFAULT NULL,
  `date_release` int(11) DEFAULT NULL,
  `website` varchar(64) DEFAULT NULL,
  `slogan` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=latin1;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
