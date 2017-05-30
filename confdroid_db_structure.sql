-- phpMyAdmin SQL Dump
-- version 4.5.4.1deb2ubuntu2
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: May 30, 2017 at 11:17 AM
-- Server version: 5.7.18-0ubuntu0.16.04.1
-- PHP Version: 7.0.15-0ubuntu0.16.04.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `confdroid_test`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `username` varchar(20) NOT NULL,
  `password` varchar(512) NOT NULL,
  `salt` varchar(28) NOT NULL,
  `authToken` text NOT NULL,
  `logintime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `ipaddr` text NOT NULL,
  `date_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `application`
--

CREATE TABLE `application` (
  `id` int(11) NOT NULL,
  `apk_name` varchar(255) NOT NULL,
  `apk_url` text NOT NULL,
  `force_install` tinyint(1) NOT NULL,
  `package_name` text NOT NULL,
  `data_dir` text NOT NULL,
  `friendly_name` varchar(255) NOT NULL,
  `date_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `application_device`
--

CREATE TABLE `application_device` (
  `device_id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `application_group`
--

CREATE TABLE `application_group` (
  `group_id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `application_sql_setting`
--

CREATE TABLE `application_sql_setting` (
  `application_id` int(11) NOT NULL,
  `sql_setting_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `application_user`
--

CREATE TABLE `application_user` (
  `user_id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `application_xml_setting`
--

CREATE TABLE `application_xml_setting` (
  `application_id` int(11) NOT NULL,
  `xml_setting_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `device`
--

CREATE TABLE `device` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `imei` varchar(20) NOT NULL,
  `date_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `group`
--

CREATE TABLE `group` (
  `id` int(11) NOT NULL,
  `prio` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `sql_setting`
--

CREATE TABLE `sql_setting` (
  `id` int(11) NOT NULL,
  `sql_setting` text NOT NULL,
  `sql_location` text NOT NULL,
  `friendly_name` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `update_log`
--

CREATE TABLE `update_log` (
  `id` int(11) NOT NULL,
  `timestamp` datetime NOT NULL,
  `json` text NOT NULL,
  `hash` varchar(300) NOT NULL,
  `imei` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `mail` varchar(255) NOT NULL,
  `auth_token` varchar(300) NOT NULL,
  `date_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `user_device`
--

CREATE TABLE `user_device` (
  `user_id` int(11) NOT NULL,
  `device_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `user_group`
--

CREATE TABLE `user_group` (
  `user_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `user_variable`
--

CREATE TABLE `user_variable` (
  `user_id` int(11) NOT NULL,
  `variables_id` int(11) NOT NULL,
  `value` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `variables`
--

CREATE TABLE `variables` (
  `id` int(11) NOT NULL,
  `name` varchar(512) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `xml_setting`
--

CREATE TABLE `xml_setting` (
  `id` int(11) NOT NULL,
  `regularexp` text NOT NULL,
  `replacewith` text NOT NULL,
  `file_location` text NOT NULL,
  `friendly_name` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `application`
--
ALTER TABLE `application`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `application_device`
--
ALTER TABLE `application_device`
  ADD KEY `Application_group_1` (`device_id`),
  ADD KEY `Application_group_2` (`application_id`);

--
-- Indexes for table `application_group`
--
ALTER TABLE `application_group`
  ADD PRIMARY KEY (`group_id`,`application_id`),
  ADD KEY `application_id` (`application_id`);

--
-- Indexes for table `application_sql_setting`
--
ALTER TABLE `application_sql_setting`
  ADD KEY `application_id` (`application_id`,`sql_setting_id`),
  ADD KEY `Application_sql_setting_2` (`sql_setting_id`);

--
-- Indexes for table `application_user`
--
ALTER TABLE `application_user`
  ADD PRIMARY KEY (`user_id`,`application_id`),
  ADD KEY `application_user_ibfk_2` (`application_id`);

--
-- Indexes for table `application_xml_setting`
--
ALTER TABLE `application_xml_setting`
  ADD PRIMARY KEY (`application_id`,`xml_setting_id`),
  ADD UNIQUE KEY `application_id` (`application_id`,`xml_setting_id`),
  ADD KEY `Application_xml_setting_2` (`xml_setting_id`);

--
-- Indexes for table `device`
--
ALTER TABLE `device`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `imei` (`imei`);

--
-- Indexes for table `group`
--
ALTER TABLE `group`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `sql_setting`
--
ALTER TABLE `sql_setting`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `update_log`
--
ALTER TABLE `update_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `mail` (`mail`),
  ADD UNIQUE KEY `auth_token` (`auth_token`);

--
-- Indexes for table `user_device`
--
ALTER TABLE `user_device`
  ADD PRIMARY KEY (`user_id`,`device_id`),
  ADD UNIQUE KEY `device_id` (`device_id`),
  ADD KEY `user_id` (`user_id`,`device_id`),
  ADD KEY `user_device_2` (`device_id`);

--
-- Indexes for table `user_group`
--
ALTER TABLE `user_group`
  ADD PRIMARY KEY (`user_id`,`group_id`),
  ADD KEY `user_group_ibfk_2` (`group_id`);

--
-- Indexes for table `user_variable`
--
ALTER TABLE `user_variable`
  ADD PRIMARY KEY (`user_id`,`variables_id`),
  ADD KEY `variables_id` (`variables_id`);

--
-- Indexes for table `variables`
--
ALTER TABLE `variables`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `xml_setting`
--
ALTER TABLE `xml_setting`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;
--
-- AUTO_INCREMENT for table `application`
--
ALTER TABLE `application`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;
--
-- AUTO_INCREMENT for table `device`
--
ALTER TABLE `device`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;
--
-- AUTO_INCREMENT for table `group`
--
ALTER TABLE `group`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;
--
-- AUTO_INCREMENT for table `sql_setting`
--
ALTER TABLE `sql_setting`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;
--
-- AUTO_INCREMENT for table `update_log`
--
ALTER TABLE `update_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;
--
-- AUTO_INCREMENT for table `variables`
--
ALTER TABLE `variables`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
--
-- AUTO_INCREMENT for table `xml_setting`
--
ALTER TABLE `xml_setting`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;
--
-- Constraints for dumped tables
--

--
-- Constraints for table `application_device`
--
ALTER TABLE `application_device`
  ADD CONSTRAINT `Application_group_1` FOREIGN KEY (`device_id`) REFERENCES `device` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `Application_group_2` FOREIGN KEY (`application_id`) REFERENCES `application` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `application_group`
--
ALTER TABLE `application_group`
  ADD CONSTRAINT `application_group_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `group` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `application_group_ibfk_2` FOREIGN KEY (`application_id`) REFERENCES `application` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `application_sql_setting`
--
ALTER TABLE `application_sql_setting`
  ADD CONSTRAINT `Application_sql_setting_1` FOREIGN KEY (`application_id`) REFERENCES `application` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `Application_sql_setting_2` FOREIGN KEY (`sql_setting_id`) REFERENCES `sql_setting` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `application_user`
--
ALTER TABLE `application_user`
  ADD CONSTRAINT `application_user_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  ADD CONSTRAINT `application_user_ibfk_2` FOREIGN KEY (`application_id`) REFERENCES `application` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `application_xml_setting`
--
ALTER TABLE `application_xml_setting`
  ADD CONSTRAINT `Application_xml_setting_1` FOREIGN KEY (`application_id`) REFERENCES `application` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `Application_xml_setting_2` FOREIGN KEY (`xml_setting_id`) REFERENCES `xml_setting` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_device`
--
ALTER TABLE `user_device`
  ADD CONSTRAINT `user_device_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  ADD CONSTRAINT `user_device_2` FOREIGN KEY (`device_id`) REFERENCES `device` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `user_group`
--
ALTER TABLE `user_group`
  ADD CONSTRAINT `user_group_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  ADD CONSTRAINT `user_group_ibfk_2` FOREIGN KEY (`group_id`) REFERENCES `group` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `user_variable`
--
ALTER TABLE `user_variable`
  ADD CONSTRAINT `user_variable_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  ADD CONSTRAINT `user_variable_ibfk_2` FOREIGN KEY (`variables_id`) REFERENCES `variables` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
