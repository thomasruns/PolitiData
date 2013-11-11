CREATE TABLE `candidate` (
  `candidate_id` bigint(32) unsigned NOT NULL AUTO_INCREMENT,
  `fec_id` varchar(9) DEFAULT NULL,
  `name_candidate` varchar(100) DEFAULT NULL,
  `party_code` varchar(3) DEFAULT NULL,
  `party_code2` varchar(3) DEFAULT NULL,
  `cand_type` varchar(1) DEFAULT NULL,
  `address1` varchar(50) DEFAULT NULL,
  `address2` varchar(30) DEFAULT NULL,
  `city` varchar(30) DEFAULT NULL,
  `state` varchar(2) DEFAULT NULL,
  `zip` varchar(10) DEFAULT NULL,
  `election_year` varchar(2) DEFAULT NULL,
  `district` varchar(2) DEFAULT NULL,
  `date_created` datetime DEFAULT NULL,
  `date_modified` datetime DEFAULT NULL,
  `status_id` int(3) DEFAULT NULL
);