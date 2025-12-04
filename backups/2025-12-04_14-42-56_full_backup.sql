-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: elms_bts
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `activities`
--

DROP TABLE IF EXISTS `activities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `trainer_id` varchar(20) NOT NULL,
  `action` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `timestamp` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`trainer_id`),
  CONSTRAINT `activities_ibfk_1` FOREIGN KEY (`trainer_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activities`
--

LOCK TABLES `activities` WRITE;
/*!40000 ALTER TABLE `activities` DISABLE KEYS */;
/*!40000 ALTER TABLE `activities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `activity_submissions`
--

DROP TABLE IF EXISTS `activity_submissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activity_submissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `activity_id` int(11) DEFAULT NULL,
  `guest_id` varchar(50) DEFAULT NULL,
  `submission_text` text DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `score` decimal(5,2) DEFAULT NULL,
  `graded_by` varchar(50) DEFAULT NULL,
  `graded_at` timestamp NULL DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `activity_id` (`activity_id`),
  KEY `trainee_id` (`guest_id`),
  CONSTRAINT `activity_submissions_ibfk_1` FOREIGN KEY (`activity_id`) REFERENCES `topic_activities` (`id`),
  CONSTRAINT `activity_submissions_ibfk_2` FOREIGN KEY (`guest_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_submissions`
--

LOCK TABLES `activity_submissions` WRITE;
/*!40000 ALTER TABLE `activity_submissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `activity_submissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `announcements`
--

DROP TABLE IF EXISTS `announcements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `announcements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `posted_by` varchar(20) DEFAULT NULL,
  `date_posted` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `posted_by` (`posted_by`),
  CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`posted_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `announcements`
--

LOCK TABLES `announcements` WRITE;
/*!40000 ALTER TABLE `announcements` DISABLE KEYS */;
/*!40000 ALTER TABLE `announcements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `batch_assignment_status`
--

DROP TABLE IF EXISTS `batch_assignment_status`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `batch_assignment_status` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_code` varchar(50) NOT NULL,
  `batch_name` varchar(100) NOT NULL,
  `trainer_id` varchar(50) DEFAULT NULL,
  `trainee_id` varchar(50) DEFAULT NULL,
  `status` enum('active','archived') NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `assigned_at` timestamp NULL DEFAULT NULL,
  `archived_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_trainer` (`trainer_id`),
  KEY `idx_trainee` (`trainee_id`),
  KEY `idx_course_batch` (`course_code`,`batch_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `batch_assignment_status`
--

LOCK TABLES `batch_assignment_status` WRITE;
/*!40000 ALTER TABLE `batch_assignment_status` DISABLE KEYS */;
/*!40000 ALTER TABLE `batch_assignment_status` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `batch_assignments`
--

DROP TABLE IF EXISTS `batch_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `batch_assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `trainee_id` varchar(20) NOT NULL,
  `trainer_id` varchar(50) NOT NULL,
  `course_code` varchar(50) NOT NULL,
  `batch_name` text NOT NULL,
  `date_assigned` datetime DEFAULT current_timestamp(),
  `assigned_by` varchar(20) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `trainee_id` (`trainee_id`),
  KEY `course_code` (`course_code`),
  KEY `assigned_by` (`assigned_by`),
  KEY `batch_id` (`batch_name`(768)),
  KEY `trainer_id` (`trainer_id`),
  CONSTRAINT `batch_assignments_ibfk_1` FOREIGN KEY (`trainee_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `batch_assignments_ibfk_2` FOREIGN KEY (`course_code`) REFERENCES `courses` (`course_code`),
  CONSTRAINT `batch_assignments_ibfk_3` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`user_id`),
  CONSTRAINT `batch_assignments_ibfk_5` FOREIGN KEY (`trainer_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `batch_assignments`
--

LOCK TABLES `batch_assignments` WRITE;
/*!40000 ALTER TABLE `batch_assignments` DISABLE KEYS */;
/*!40000 ALTER TABLE `batch_assignments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `branches`
--

DROP TABLE IF EXISTS `branches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `branches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `branch_name` varchar(255) NOT NULL,
  `branch_code` varchar(50) NOT NULL,
  `address` text DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `branch_code` (`branch_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `branches`
--

LOCK TABLES `branches` WRITE;
/*!40000 ALTER TABLE `branches` DISABLE KEYS */;
/*!40000 ALTER TABLE `branches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `certificates`
--

DROP TABLE IF EXISTS `certificates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `certificates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `trainee_id` varchar(50) NOT NULL,
  `course_code` varchar(50) NOT NULL,
  `certificate_number` varchar(100) NOT NULL,
  `issue_date` date NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `final_grade` decimal(5,2) DEFAULT NULL,
  `certificate_path` varchar(500) DEFAULT NULL,
  `status` enum('active','expired','revoked') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `certificate_number` (`certificate_number`),
  KEY `trainee_id` (`trainee_id`),
  KEY `course_code` (`course_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `certificates`
--

LOCK TABLES `certificates` WRITE;
/*!40000 ALTER TABLE `certificates` DISABLE KEYS */;
/*!40000 ALTER TABLE `certificates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `competencies`
--

DROP TABLE IF EXISTS `competencies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `competencies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_id` int(11) NOT NULL,
  `competency_code` varchar(50) NOT NULL,
  `unit_order` int(11) unsigned DEFAULT NULL,
  `competency_name` varchar(255) NOT NULL,
  `module_title` varchar(255) NOT NULL,
  `competency_type` enum('basic','common','core') NOT NULL,
  `nominal_hours` int(11) unsigned DEFAULT NULL,
  `description` text DEFAULT NULL,
  `learning_outcomes` mediumtext DEFAULT NULL,
  `hours_per_session` int(11) NOT NULL,
  `status` enum('active','archived') DEFAULT 'active',
  `date_created` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `competency_code` (`competency_code`),
  KEY `competencies_ibfk_1` (`course_id`),
  KEY `idx_comp_course_type_order` (`course_id`,`competency_type`,`unit_order`),
  CONSTRAINT `competencies_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=140 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `competencies`
--

LOCK TABLES `competencies` WRITE;
/*!40000 ALTER TABLE `competencies` DISABLE KEYS */;
INSERT INTO `competencies` (`id`, `course_id`, `competency_code`, `unit_order`, `competency_name`, `module_title`, `competency_type`, `nominal_hours`, `description`, `learning_outcomes`, `hours_per_session`, `status`, `date_created`) VALUES (1,1,'HRD-B01',1,'Participate in workplace communication','Participating in workplace communication','basic',6,NULL,'Obtain and convey workplace information; Perform duties following workplace instructions; Complete relevant work-related documents.',4,'active','2025-12-04 13:41:47'),(2,1,'HRD-B02',2,'Work in a team environment','Working in a team environment','basic',3,NULL,'Describe team role and scope; Identify one’s role and responsibility within team; Work as a team member.',4,'active','2025-12-04 13:41:47'),(3,1,'HRD-B03',3,'Solve/address general workplace problems','Solving/addressing general workplace problems','basic',3,NULL,'Identify routine problems; Look for solutions to routine problems; Recommend solutions to problems.',4,'active','2025-12-04 13:41:47'),(4,1,'HRD-B04',4,'Develop career and life decisions','Developing career and life decisions','basic',3,NULL,'Manage one’s emotion; Develop reflective practice; Boost self-confidence and self-regulation.',4,'active','2025-12-04 13:41:47'),(5,1,'HRD-B05',5,'Contribute to workplace innovation','Contributing to workplace innovation','basic',3,NULL,'Identify opportunities to do things better; Discuss and develop ideas with others; Integrate ideas for change in the workplace.',4,'active','2025-12-04 13:41:47'),(6,1,'HRD-B06',6,'Present relevant information','Present relevant information','basic',8,NULL,'Gather data/information; Assess gathered data/information; Record and present information.',4,'active','2025-12-04 13:41:47'),(7,1,'HRD-B07',7,'Practice occupational health and safety procedures','Practicing occupational health and safety procedures','basic',4,NULL,'Identify OSH compliance requirements; Prepare OSH requirements for compliance; Perform tasks per OSH policies and procedures.',4,'active','2025-12-04 13:41:47'),(8,1,'HRD-B08',8,'Exercise efficient and effective sustainable practices','Exercising efficient and effective sustainable practices in the workplace','basic',3,NULL,'Identify efficiency and effectiveness of resource utilization; Determine causes of inefficiency/ineffectiveness; Convey inefficient and ineffective environmental practices.',4,'active','2025-12-04 13:41:47'),(9,1,'HRD-B09',9,'Practice entrepreneurial skills in the workplace','Practicing entrepreneurial skills in the workplace','basic',4,NULL,'Apply entrepreneurial workplace best practices; Communicate entrepreneurial best practices; Implement cost-effective operations.',4,'active','2025-12-04 13:41:47'),(10,1,'HRD-C01',1,'Maintain an effective relationship with clients/customers','Maintaining an effective relationship with clients/customers','common',6,NULL,'Maintain a professional image; Meet client requirements; Build credibility; Establish professional relationships; Manage interactions; Provide effective responses to enquiries.',4,'active','2025-12-04 13:41:47'),(11,1,'HRD-C02',2,'Manage own performance','Managing own performance','common',6,NULL,'Plan for completion of own workload; Maintain quality of performance; Build credibility with customers/clients.',4,'active','2025-12-04 13:41:47'),(12,1,'HRD-C03',3,'Apply quality standards','Applying quality standards','common',6,NULL,'Assess client service needs; Assess own work; Engage in quality improvement.',4,'active','2025-12-04 13:41:47'),(13,1,'HRD-C04',4,'Maintain a safe clean and efficient work environment','Maintaining a safe clean and efficient work environment','common',6,NULL,'Comply with health regulations; Assess own work; Check and maintain tools, equipment and stocks; Provide a relaxed and caring environment.',4,'active','2025-12-04 13:41:47'),(14,1,'HRD-CORE01',1,'Perform pre and post hair care activities','Performing pre and post hair care activities','core',40,NULL,'Prepare client and workstation; Apply shampoo/conditioner; Perform blow dry; Perform post-service activities.',4,'active','2025-12-04 13:41:47'),(15,1,'HRD-CORE02',2,'Perform Hair and Scalp Treatment','Performing Hair and Scalp Treatment','core',40,NULL,'Prepare client and workstation; Treat hair and scalp; Rinse and blow-dry; Perform post-service activities.',4,'active','2025-12-04 13:41:47'),(16,1,'HRD-CORE03',3,'Perform Basic hair coloring','Performing Basic hair coloring','core',80,NULL,'Prepare client and workstation; Apply basic hair coloring; Perform post-service activities.',4,'active','2025-12-04 13:41:47'),(17,1,'HRD-CORE04',4,'Perform Basic Hair Bleaching','Performing Basic Hair Bleaching','core',40,NULL,'Prepare client and workstation; Bleach hair; Perform post-service activities.',4,'active','2025-12-04 13:41:47'),(18,1,'HRD-CORE05',5,'Perform Basic Hair Perming','Performing Basic Hair Perming','core',80,NULL,'Prepare client and workstation; Perm hair; Apply finishing touches; Perform post-service activities.',4,'active','2025-12-04 13:41:47'),(19,1,'HRD-CORE06',6,'Perform hair straightening','Performing hair straightening','core',80,NULL,'Prepare client and workstation; Apply hair straightening products; Iron hair; Apply fixing solution; Perform post-service activities.',4,'active','2025-12-04 13:41:47'),(20,1,'HRD-CORE07',7,'Perform basic haircutting','Performing basic haircutting','core',120,NULL,'Prepare client and workstation; Cut hair; Check haircut and apply finishing touches; Perform post-service activities.',4,'active','2025-12-04 13:41:47'),(21,2,'AUT-B01',1,'Receive and respond to workplace communication','Receiving and responding to workplace communication','basic',8,NULL,'Follow routine spoken messages; Perform workplace duties following written notices.',4,'active','2025-12-04 13:41:47'),(22,2,'AUT-B02',2,'Work with others','Working with others','basic',3,NULL,'Develop effective workplace relationships; Contribute to work group activities.',4,'active','2025-12-04 13:41:47'),(23,2,'AUT-B03',3,'Solve/address routine problems','Solving/addressing routine problems','basic',4,NULL,'Identify the problem; Assess fundamental causes; Determine corrective action; Communicate action plans and recommendations.',4,'active','2025-12-04 13:41:47'),(24,2,'AUT-B04',4,'Enhance self-management skills','Enhancing self-management skills','basic',3,NULL,'Set personal and career goals; Recognize emotions; Describe oneself as a learner.',4,'active','2025-12-04 13:41:47'),(25,2,'AUT-B05',5,'Support innovation','Supporting innovation','basic',3,NULL,'Identify need for innovation; Recognize innovative and creative ideas; Support flexible and innovative ways of working.',4,'active','2025-12-04 13:41:47'),(26,2,'AUT-B06',6,'Access and maintain information','Accessing and maintaining information','basic',12,NULL,'Identify and gather needed information; Search on internet/intranet; Examine, secure, and manage information.',4,'active','2025-12-04 13:41:47'),(27,2,'AUT-B07',7,'Follow occupational safety and health policies and procedures','Following occupational safety and health policies and procedures','basic',8,NULL,'Identify relevant OSH policies and procedures; Perform relevant OSH procedures; Comply with OSH policies and standards.',4,'active','2025-12-04 13:41:47'),(28,2,'AUT-B08',8,'Apply environmental work standards','Applying Environmental Work Standards','basic',3,NULL,'Identify environmental work hazards; Follow environmental work procedures; Comply with environmental work requirements.',4,'active','2025-12-04 13:41:47'),(29,2,'AUT-B09',9,'Adopt entrepreneurial mindset in the workplace','Adopting entrepreneurial mindset in the workplace','basic',3,NULL,'Determine entrepreneurial mindset; Identify entrepreneurial practices.',4,'active','2025-12-04 13:41:47'),(30,2,'AUT-C01',1,'Validate vehicle specification','Validating vehicle specification','common',17,NULL,'Check body type; Check vehicle engine type; Check vehicle specifications; Complete validation of vehicle specification.',4,'active','2025-12-04 13:41:47'),(31,2,'AUT-C02',2,'Move and Position Vehicle','Moving and Positioning Vehicle','common',40,NULL,'Prepare vehicle for operation; Position vehicle; Park and stop the vehicle.',4,'active','2025-12-04 13:41:47'),(32,2,'AUT-C03',3,'Utilize automotive tools','Utilizing automotive tools','common',16,NULL,'Prepare automotive tools; Use automotive tools; Maintain automotive tools.',4,'active','2025-12-04 13:41:47'),(33,2,'AUT-C04',4,'Perform mensuration and calculation','Performing mensuration and calculation','common',43,NULL,'Select measuring instruments; Carry out measurements and calculation; Maintain measuring instruments.',4,'active','2025-12-04 13:41:47'),(34,2,'AUT-C05',5,'Utilize workshop facilities and equipment','Utilizing workshop facilities and equipment','common',19,NULL,'Perform pre-operation activities; Use facilities and equipment; Conduct post-operation activities.',4,'active','2025-12-04 13:41:47'),(35,2,'AUT-C06',6,'Prepare servicing parts and consumables','Preparing servicing parts and consumables','common',13,NULL,'Identify parts and consumables; Retrieve and withdraw parts and consumables; Complete work process.',4,'active','2025-12-04 13:41:47'),(36,2,'AUT-C07',7,'Prepare vehicle for servicing and releasing','Preparing vehicle for servicing and releasing','common',14,NULL,'Receive vehicle; Prepare vehicle for servicing; Prepare vehicle for releasing.',4,'active','2025-12-04 13:41:47'),(37,2,'AUT-CORE01',1,'Perform pre-delivery inspection','Preforming pre-delivery inspection','core',33,NULL,'Prepare for pre-delivery inspection; Perform physical and functional inspection; Complete work processes.',4,'active','2025-12-04 13:41:47'),(38,2,'AUT-CORE02',2,'Perform periodic maintenance of automotive engine','Performing periodic maintenance of automotive engine','core',136,NULL,'Prepare for inspection and service engine; Inspect engine; Service engine; Complete work processes.',4,'active','2025-12-04 13:41:47'),(39,2,'AUT-CORE03',3,'Perform periodic maintenance of drive train','Performing periodic maintenance of drive train','core',28,NULL,'Perform pre-service preparations; Conduct periodic maintenance of drive trains; Perform post-service activities.',4,'active','2025-12-04 13:41:47'),(40,2,'AUT-CORE04',4,'Perform periodic maintenance of brake system','Performing periodic maintenance of brake system','core',28,NULL,'Prepare for periodic maintenance of brake system; Carry out periodic maintenance procedures; Complete periodic maintenance procedure.',4,'active','2025-12-04 13:41:47'),(41,2,'AUT-CORE05',5,'Perform periodic maintenance of suspension system','Performing periodic maintenance of suspension system','core',30,NULL,'Perform pre-periodic maintenance of suspension system; Apply periodic maintenance procedures; Perform work to completion.',4,'active','2025-12-04 13:41:47'),(42,2,'AUT-CORE06',6,'Perform periodic maintenance of steering system','Performing periodic maintenance of steering system','core',24,NULL,'Perform pre-periodic maintenance of steering system; Apply periodic maintenance procedures; Perform work to completion.',4,'active','2025-12-04 13:41:47'),(43,3,'JPN-B01',1,'Participate in workplace communication','Participating in workplace communication','basic',4,NULL,'Obtain and convey workplace information; Complete relevant work-related documents; Participate in workplace meetings and discussions.',4,'active','2025-12-04 13:41:47'),(44,3,'JPN-B02',2,'Work in a team environment','Working in a team environment','basic',4,NULL,'Describe and identify team roles and responsibilities; Work as a team member.',4,'active','2025-12-04 13:41:47'),(45,3,'JPN-B03',3,'Practice career professionalism','Practicing career professionalism','basic',2,NULL,'Integrate personal objectives with organizational goals; Set and meet work priorities; Maintain professional growth and development.',4,'active','2025-12-04 13:41:47'),(46,3,'JPN-B04',4,'Practice occupational health and safety','Practicing occupational health and safety','basic',2,NULL,'Evaluate hazards and risks; Control hazards and risks; Maintain occupational health and safety awareness.',4,'active','2025-12-04 13:41:47'),(47,3,'JPN-C01',1,'Introduce the general features of Japanese Culture','Introducing the general features of Japanese culture','common',20,NULL,'Know Japanese economic industry, arts and political history; Understand religious beliefs and traditions; Know education system; Traditional house manners; Greetings and gratitude; Introductions, parting terms and apologizing; Basic travel inside town; Japanese working ethics and Rajoo Taisoo; Hear testimonies of ex-trainees.',4,'active','2025-12-04 13:41:47'),(48,3,'JPN-C02',2,'Introduce the Japanese Work Ethics','Introducing the Japanese Work Ethics','common',4,NULL,'Know Japanese work ethics and Rajoo Taisoo; Hear testimonies and personal experiences from ex-Japanese trainees.',4,'active','2025-12-04 13:41:47'),(49,3,'JPN-CORE01',1,'Practice the basic Japanese Writing System and their Alphabets','Practicing the Basic Japanese Writing System and their alphabets','core',54,NULL,'Read and write Hiragana; Read and write Katakana; Read and write basic Kanji; Differentiate romanization systems HYUJUNSHIKI and HEPBURN.',4,'active','2025-12-04 13:41:47'),(50,3,'JPN-CORE02',2,'Practice the Japanese Sounds of Alphabets and Pronunciation','Practicing the Japanese Sounds of Alphabets and Pronunciation','core',24,NULL,'Memorize Japanese vowels, sounds and pronunciations; Practice sounds of the alphabets; Omit particles in a sentence without changing meaning.',4,'active','2025-12-04 13:41:47'),(51,3,'JPN-CORE03',3,'Practice the Japanese Grammar and Application in Sentence Construction','Practicing the Japanese Grammar and Application in Sentence Construction','core',88,NULL,'Memorize common interrogatives and uses; Practice interrogatives; Use particles, conjunctions, demonstratives, adjectives and adverbs; Differentiate \"i\" and \"na\" adjectives; Use nouns and pronouns; Apply verb functions; Conjugate verbs; Construct affirmative and negative sentences.',4,'active','2025-12-04 13:41:47'),(52,3,'JPN-CORE04',4,'Apply the Japanese Counters in a conversation','Applying the Japanese counters in a conversation','core',48,NULL,'Use counters for existence (animate/inanimate); Use counters for location (relative position); Use counters for quantity (Japanese and Chinese origin counters).',4,'active','2025-12-04 13:41:47'),(53,3,'JPN-CORE05',5,'Perform Japanese Greetings of honorifics in a conversation','Performing Japanese Greetings of honorifics in a Conversation','core',40,NULL,'Know honorifics and humble forms; Distinguish honorific/humble vs polite/plain forms; Manage ambiguous expressions; Apply word repetition rules; Change word order without changing meaning.',4,'active','2025-12-04 13:41:47'),(54,3,'JPN-CORE06',6,'Participate in Ordinary Conversation','Participating in ordinary conversation','core',60,NULL,'Initiate conversation; Respond to different topics; Respond quickly; Speak while watching listener reaction; Deliver encouragement; Use confirmations; Express assertions; Express appreciation and apology; Complain and reject offers; Ask indirect questions.',4,'active','2025-12-04 13:41:47'),(55,4,'DRS-B01',1,'Participate in workplace communication','Participating in workplace communication','basic',4,NULL,'Obtain and convey workplace information; Complete relevant work-related documents; Participate in workplace meetings and discussions.',4,'active','2025-12-04 13:41:47'),(56,4,'DRS-B02',2,'Work in a team environment','Working in a team environment','basic',4,NULL,'Describe and identify team roles and responsibilities; Work as a team member.',4,'active','2025-12-04 13:41:47'),(57,4,'DRS-B03',3,'Practice career professionalism','Practicing career professionalism','basic',2,NULL,'Integrate personal objectives with organizational goals; Set and meet work priorities; Maintain professional growth and development.',4,'active','2025-12-04 13:41:47'),(58,4,'DRS-B04',4,'Practice occupational health and safety','Practicing occupational health and safety','basic',2,NULL,'Evaluate hazards and risks; Control hazards and risks; Maintain occupational health and safety awareness.',4,'active','2025-12-04 13:41:47'),(59,4,'DRS-C01',1,'Carry Out Measurements and Calculations','Carrying-out Measurements and Calculations','common',4,NULL,'Obtain measurements; Perform simple calculations; Estimate appropriate quantities.',4,'active','2025-12-04 13:41:47'),(60,4,'DRS-C02',2,'Apply Quality Standards','Applying Quality Standards','common',5,NULL,'Assess own work based on workplace standards; Assess quality of component parts and take corrective actions; Measure parts using appropriate instruments; Record information per procedures; Study causes of quality deviation and recommend preventive actions.',4,'active','2025-12-04 13:41:47'),(61,4,'DRS-C03',3,'Perform Basic Maintenance','Performing Basic Maintenance','common',3,NULL,'Operate machine and assess its performance; Clean and lubricate machine; Check machine operation.',4,'active','2025-12-04 13:41:47'),(62,4,'DRS-C04',4,'Set Up and Operate Machines','Setting-up and Operating Machines','common',5,NULL,'Set machine; Conduct sample run; Test machine output; Re-adjust settings to meet requirements; Maintain records and prepare reports.',4,'active','2025-12-04 13:41:47'),(63,4,'DRS-CORE01',1,'Draft and Cut Pattern for Casual Apparel','Drafting and Cutting Pattern for Casual Apparel','core',80,NULL,'Plan garment design; Take client body measurements; Draft basic/block pattern; Manipulate pattern; Cut final pattern.',4,'active','2025-12-04 13:41:47'),(64,4,'DRS-CORE02',2,'Prepare and Cut Materials for Casual Apparel','Preparing and Cutting Materials for Casual Apparel','core',40,NULL,'Prepare materials (fabric); Lay-out and mark pattern on materials; Cut materials.',4,'active','2025-12-04 13:41:47'),(65,4,'DRS-CORE03',3,'Sew Casual Apparel','Sewing casual apparel','core',80,NULL,'Prepare cut parts; Prepare sewing machine for operation; Sew and assemble garment parts; Alter completed garments.',4,'active','2025-12-04 13:41:47'),(66,4,'DRS-CORE04',4,'Apply Finishing Touches on Casual Apparel','Applying Finishing Touches on Casual Apparel','core',40,NULL,'Apply finishing touches; Trim excess threads; Press finished garment; Package the finished garment.',4,'active','2025-12-04 13:41:47'),(67,5,'BPP-B01',1,'Participate in workplace communication','Participating in workplace communication','basic',6,NULL,'Obtain and convey workplace information; Perform duties following workplace instructions; Complete relevant work-related documents.',4,'active','2025-12-04 13:41:47'),(68,5,'BPP-B02',2,'Work in a team environment','Working in a team environment','basic',3,NULL,'Describe team role and scope; Identify one’s role and responsibility; Work as a team member.',4,'active','2025-12-04 13:41:47'),(69,5,'BPP-B03',3,'Solve/address general workplace problems','Solving/addressing general workplace problems','basic',3,NULL,'Identify routine problems; Look for solutions to routine problems; Recommend solutions to problems.',4,'active','2025-12-04 13:41:47'),(70,5,'BPP-B04',4,'Develop career and life decisions','Developing career and life decisions','basic',3,NULL,'Manage one’s emotion; Develop reflective practice; Boost self-confidence and self-regulation.',4,'active','2025-12-04 13:41:47'),(71,5,'BPP-B05',5,'Contribute to workplace innovation','Contributing to workplace innovation','basic',3,NULL,'Identify opportunities to do things better; Discuss and develop ideas with others; Integrate ideas for change in the workplace.',4,'active','2025-12-04 13:41:47'),(72,5,'BPP-B06',6,'Present relevant information','Present relevant information','basic',8,NULL,'Gather data/information; Assess gathered data/information; Record and present information.',4,'active','2025-12-04 13:41:47'),(73,5,'BPP-B07',7,'Practice occupational health and safety procedures','Practicing occupational health and safety procedures','basic',4,NULL,'Identify OSH compliance requirements; Prepare OSH requirements for compliance; Perform tasks per OSH policies and procedures.',4,'active','2025-12-04 13:41:47'),(74,5,'BPP-B08',8,'Exercise efficient and effective sustainable practices in the workplace','Exercising efficient and effective sustainable practices in the workplace','basic',3,NULL,'Identify efficiency and effectiveness of resource utilization; Determine causes of inefficiency/ineffectiveness; Convey inefficient and ineffective environmental practices.',4,'active','2025-12-04 13:41:47'),(75,5,'BPP-B09',9,'Practice entrepreneurial skills in the workplace','Practicing entrepreneurial skills in the workplace','basic',4,NULL,'Apply entrepreneurial workplace best practices; Communicate entrepreneurial workplace best practices; Implement cost-effective operations.',4,'active','2025-12-04 13:41:47'),(76,5,'BPP-C01',1,'Develop and Updated Industry Knowledge','Developing and Updating Industry Knowledge','common',2,NULL,'Seek information on the industry; Update industry knowledge.',4,'active','2025-12-04 13:41:47'),(77,5,'BPP-C02',2,'Observe Workplace Hygiene Procedures','Observing Workplace Hygiene Procedures','common',4,NULL,'Follow hygienic procedures; Identify and present hygienic risks.',4,'active','2025-12-04 13:41:47'),(78,5,'BPP-C03',3,'Perform Computer Operations','Performing Computer Operations','common',4,NULL,'Plan and prepare for tasks; Input data; Access information; Produce outputs using computer systems.',4,'active','2025-12-04 13:41:47'),(79,5,'BPP-C04',4,'Perform Workplace and Safety Practices','Performing Workplace and Safety Practices','common',4,NULL,'Follow workplace and safety practices; Deal with emergency situations; Maintain safe personal preventive standards.',4,'active','2025-12-04 13:41:47'),(80,5,'BPP-C05',5,'Provide Effective Customer Service','Providing Effective Customer Service','common',4,NULL,'Greet customers; Deliver service; Identify customer needs; Handle queries via phone, fax, internet; Handle complaints, evaluation and recommendations.',4,'active','2025-12-04 13:41:47'),(81,5,'BPP-CORE01',1,'Prepare and produce bakery products','Preparing and producing bakery products','core',25,NULL,'Prepare bakery products; Decorate and present bakery products; Store bakery products.',4,'active','2025-12-04 13:41:47'),(82,5,'BPP-CORE02',2,'Prepare and produce pastry products','Preparing and producing pastry products','core',25,NULL,'Prepare pastry products; Decorate and present pastry products; Store pastry products.',4,'active','2025-12-04 13:41:47'),(83,5,'BPP-CORE03',3,'Prepare and present gateaux, tortes and cakes','Preparing and presenting gateaux, tortes and cakes','core',25,NULL,'Prepare sponge and cakes; Prepare and use fillings; Decorate cakes; Present cakes; Store cakes.',4,'active','2025-12-04 13:41:47'),(84,5,'BPP-CORE04',4,'Prepare and display petits fours','Preparing and displaying petits fours','core',20,NULL,'Prepare iced, fresh, marzipan and caramelized petits fours; Display petits fours; Store petits fours.',4,'active','2025-12-04 13:41:47'),(85,5,'BPP-CORE05',5,'Prepare and serve other types of desserts','Preparing and serving other types of desserts','core',10,NULL,'Prepare other types of desserts; Plan, prepare and conduct a dessert trolley presentation; Store and package desserts.',4,'active','2025-12-04 13:41:47'),(86,6,'TLR-B01',1,'Participate in workplace communication','Participating in workplace communication','basic',6,NULL,'Obtain and convey workplace information; Perform duties following workplace instructions; Complete relevant work-related documents.',4,'active','2025-12-04 13:41:47'),(87,6,'TLR-B02',2,'Work in a team environment','Working in a team environment','basic',3,NULL,'Describe team role and scope; Identify one’s role and responsibility within team; Work as a team member.',4,'active','2025-12-04 13:41:47'),(88,6,'TLR-B03',3,'Solve/address general workplace problems','Solving/addressing general workplace problems','basic',3,NULL,'Identify routine problems; Look for solutions to routine problems; Recommend solutions to problems.',4,'active','2025-12-04 13:41:47'),(89,6,'TLR-B04',4,'Develop career and life decisions','Developing career and life decisions','basic',3,NULL,'Manage one’s emotion; Develop reflective practice; Boost self-confidence and self-regulation.',4,'active','2025-12-04 13:41:47'),(90,6,'TLR-B05',5,'Contribute to workplace innovation','Contributing to workplace innovation','basic',3,NULL,'Identify opportunities to do things better; Discuss and develop ideas with others; Integrate ideas for change in the workplace.',4,'active','2025-12-04 13:41:47'),(91,6,'TLR-B06',6,'Present relevant information','Present relevant information','basic',8,NULL,'Gather data/information; Assess gathered data/information; Record and present information.',4,'active','2025-12-04 13:41:47'),(92,6,'TLR-B07',7,'Practice occupational health and safety procedures','Practicing occupational health and safety procedures','basic',4,NULL,'Identify OSH compliance requirements; Prepare OSH requirements for compliance; Perform tasks in accordance with OSH policies and procedures.',4,'active','2025-12-04 13:41:47'),(93,6,'TLR-B08',8,'Exercise efficient and effective sustainable practices in the workplace','Exercising efficient and effective sustainable practices in the workplace','basic',3,NULL,'Identify efficiency and effectiveness of resource utilization; Determine causes of inefficiency/ineffectiveness; Convey inefficient and ineffective environmental practices.',4,'active','2025-12-04 13:41:47'),(94,6,'TLR-B09',9,'Practice entrepreneurial skills in the workplace','Practicing entrepreneurial skills in the workplace','basic',4,NULL,'Apply entrepreneurial workplace best practices; Communicate entrepreneurial workplace best practices; Implement cost-effective operations.',4,'active','2025-12-04 13:41:47'),(95,6,'TLR-C01',1,'Carry Out Measurements and Calculations','Carrying-out Measurements and Calculations','common',4,NULL,'Obtain measurements; Perform simple calculations; Estimate appropriate quantities.',4,'active','2025-12-04 13:41:47'),(96,6,'TLR-C02',2,'Apply Quality Standards','Applying Quality Standards','common',5,NULL,'Assess own work based on workplace standards; Assess quality of component parts and take corrective actions; Measure parts using appropriate instruments; Record information per procedures; Study causes of quality deviation and recommend preventive actions.',4,'active','2025-12-04 13:41:47'),(97,6,'TLR-C03',3,'Perform Basic Maintenance','Performing Basic Maintenance','common',3,NULL,'Operate machine and assess its performance; Clean and lubricate machine; Check machine operation.',4,'active','2025-12-04 13:41:47'),(98,6,'TLR-C04',4,'Set Up and Operate Machines','Setting-up and Operating Machines','common',5,NULL,'Set machine; Conduct sample run; Test machine output; Re-adjust machine settings to meet requirements; Maintain records and prepare reports.',4,'active','2025-12-04 13:41:47'),(99,6,'TLR-CORE01',1,'Draft and Cut Pattern for Casual Apparel','Drafting and Cutting Pattern for Casual Apparel','core',80,NULL,'Plan garment design; Take client body measurements; Draft basic/block pattern; Manipulate pattern; Cut final pattern.',4,'active','2025-12-04 13:41:47'),(100,6,'TLR-CORE02',2,'Prepare and Cut Materials for Casual Apparel','Preparing and Cutting Materials for Casual Apparel','core',40,NULL,'Prepare materials (fabric); Lay-out and mark pattern on materials; Cut materials.',4,'active','2025-12-04 13:41:47'),(101,6,'TLR-CORE03',3,'Sew Casual Apparel','Sewing casual apparel','core',80,NULL,'Prepare cut parts; Prepare sewing machine for operation; Sew and assemble garment parts; Alter completed garments.',4,'active','2025-12-04 13:41:47'),(102,6,'TLR-CORE04',4,'Apply Finishing Touches on Casual Apparel','Applying Finishing Touches on Casual Apparel','core',40,NULL,'Apply finishing touches; Trim excess threads; Press finished garment; Package the finished garment.',4,'active','2025-12-04 13:41:47'),(103,7,'ACP-B01',1,'Participate in workplace communication','Participating in workplace communication','basic',4,NULL,'Obtain and convey workplace information; Participate in workplace meetings and discussions; Complete relevant work-related documents.',4,'active','2025-12-04 13:41:47'),(104,7,'ACP-B02',2,'Work in a team environment','Working in a team environment','basic',4,NULL,'Acknowledge Gender and Development and other laws; Describe and identify team role and responsibility; Work as a team member.',4,'active','2025-12-04 13:41:47'),(105,7,'ACP-B03',3,'Solve/address general workplace problems','Solving/addressing general workplace problems','basic',4,NULL,'Identify routine problems; Look for solutions to routine problems; Recommend solutions to problems.',4,'active','2025-12-04 13:41:47'),(106,7,'ACP-B04',4,'Develop career and life decisions','Developing career and life decisions','basic',4,NULL,'Manage one’s emotion; Develop reflective practice; Boost self-confidence and self-regulation.',4,'active','2025-12-04 13:41:47'),(107,7,'ACP-B05',5,'Contribute to workplace innovation','Contributing to workplace innovation','basic',4,NULL,'Identify opportunities to do things better; Discuss and develop ideas with others; Integrate ideas for change in the workplace.',4,'active','2025-12-04 13:41:47'),(108,7,'ACP-B06',6,'Present relevant information','Present relevant information','basic',4,NULL,'Gather data/information; Assess gathered data/information; Record and present information.',4,'active','2025-12-04 13:41:47'),(109,7,'ACP-B07',7,'Practice occupational health and safety procedures','Practicing occupational health and safety procedures','basic',4,NULL,'Identify hazards and risks; Evaluate hazards and risks; Control hazards and risks; Maintain occupational health and safety awareness.',4,'active','2025-12-04 13:41:47'),(110,7,'ACP-B08',8,'Exercise efficient and effective sustainable practices in the workplace','Exercising efficient and effective sustainable practices in the workplace','basic',4,NULL,'Identify efficiency and effectiveness of resource utilization; Determine causes of inefficiency/ineffectiveness; Convey inefficient and ineffective environmental practices.',4,'active','2025-12-04 13:41:47'),(111,7,'ACP-B09',9,'Practice entrepreneurial skills in the workplace','Practicing entrepreneurial skills in the workplace','basic',5,NULL,'Apply entrepreneurial workplace best practices; Communicate entrepreneurial workplace best practices; Implement cost-effective operations.',4,'active','2025-12-04 13:41:47'),(112,7,'ACP-C01',1,'Apply Safety Measures in Farm Operations','Applying Safety Measures in Farm Operations','common',12,NULL,'Apply appropriate safety measures while working in farm; Safe keep/dispose tools, materials and outfit.',4,'active','2025-12-04 13:41:47'),(113,7,'ACP-C02',2,'Use farm tools and equipment','Using farm tools and equipment','common',12,NULL,'Prepare and use farm tools; Prepare and operate farm equipment; Perform preventive maintenance procedures/practices.',4,'active','2025-12-04 13:41:47'),(114,7,'ACP-C03',3,'Perform estimation and basic calculation','Performing estimation and basic calculation','common',10,NULL,'Perform estimation; Perform basic workplace calculation.',4,'active','2025-12-04 13:41:47'),(115,7,'ACP-C04',4,'Process farm wastes','Processing farm wastes','common',12,NULL,'Collect farm wastes; Identify and segregate wastes; Treat and process farm wastes; Perform housekeeping.',4,'active','2025-12-04 13:41:47'),(116,7,'ACP-C05',5,'Perform record keeping','Performing record keeping','common',12,NULL,'Carry out inventory activities; Maintain production records; Prepare financial records.',4,'active','2025-12-04 13:41:47'),(117,7,'ACP-CORE01',1,'Perform nursery operations','Performing nursery operations','core',65,NULL,'Prepare nursery tools, farm implements and simple equipment; Maintain nursery facilities; Handle seeds/planting material; Prepare growing media; Conduct propagation activities.',4,'active','2025-12-04 13:41:47'),(118,7,'ACP-CORE02',2,'Plant crops','Planting crops','core',65,NULL,'Prepare land for planting; Conduct field lay-out; Dig holes; Perform direct seeding; Transplant seedlings.',4,'active','2025-12-04 13:41:47'),(119,7,'ACP-CORE03',3,'Care and maintain crops','Caring and maintaining crops','core',65,NULL,'Apply pest control measures; Apply fertilizer; Water crops; Perform pruning; Perform physical growth enhancing practices.',4,'active','2025-12-04 13:41:47'),(120,7,'ACP-CORE04',4,'Carry-out harvest and postharvest operations','Carrying-out harvest and postharvest operations','core',65,NULL,'Perform pre-harvest operations; Perform harvesting activities; Perform postharvest operations; Monitor storage pests and diseases.',4,'active','2025-12-04 13:41:47'),(121,8,'DRV-B01',1,'Participate in workplace communication','Participating in workplace communication','basic',4,NULL,'Obtain and convey workplace information; Participate in workplace meetings and discussions; Complete relevant work-related documents.',4,'active','2025-12-04 13:41:47'),(122,8,'DRV-B02',2,'Work in a team environment','Working in a team environment','basic',4,NULL,'Acknowledge Gender and Development and other laws; Describe and identify team role and responsibility; Work as a team member.',4,'active','2025-12-04 13:41:47'),(123,8,'DRV-B03',3,'Solve/address general workplace problems','Solving/addressing general workplace problems','basic',4,NULL,'Identify routine problems; Look for solutions to routine problems; Recommend solutions to problems.',4,'active','2025-12-04 13:41:47'),(124,8,'DRV-B04',4,'Develop career and life decisions','Developing career and life decisions','basic',4,NULL,'Manage one’s emotion; Develop reflective practice; Boost self-confidence and self-regulation.',4,'active','2025-12-04 13:41:47'),(125,8,'DRV-B05',5,'Contribute to workplace innovation','Contributing to workplace innovation','basic',4,NULL,'Identify opportunities to do things better; Discuss and develop ideas with others; Integrate ideas for change in the workplace.',4,'active','2025-12-04 13:41:47'),(126,8,'DRV-B06',6,'Present relevant information','Present relevant information','basic',4,NULL,'Gather data/information; Assess gathered data/information; Record and present information.',4,'active','2025-12-04 13:41:47'),(127,8,'DRV-B07',7,'Practice occupational health and safety procedures','Practicing occupational health and safety procedures','basic',4,NULL,'Identify hazards and risks; Evaluate hazards and risks; Control hazards and risks; Maintain occupational health and safety awareness.',4,'active','2025-12-04 13:41:47'),(128,8,'DRV-B08',8,'Exercise efficient and effective sustainable practices in the workplace','Exercising efficient and effective sustainable practices in the workplace','basic',4,NULL,'Identify efficiency and effectiveness of resource utilization; Determine causes of inefficiency/ineffectiveness; Convey inefficient and ineffective environmental practices.',4,'active','2025-12-04 13:41:47'),(129,8,'DRV-B09',9,'Practice entrepreneurial skills in the workplace','Practicing entrepreneurial skills in the workplace','basic',5,NULL,'Apply entrepreneurial workplace best practices; Communicate entrepreneurial workplace best practices; Implement cost-effective operations.',4,'active','2025-12-04 13:41:47'),(130,8,'DRV-C01',1,'Apply appropriate sealant/adhesive','Applying appropriate sealant/adhesive','common',3,NULL,'Identify appropriate sealant/adhesive; Prepare surface for application; Store/dispose used sealant/adhesive.',4,'active','2025-12-04 13:41:47'),(131,8,'DRV-C02',2,'Perform shop maintenance','Performing shop maintenance','common',4,NULL,'Select measuring instruments; Carry out measurement and calculation; Maintain measuring instruments.',4,'active','2025-12-04 13:41:47'),(132,8,'DRV-C03',3,'Read, Interpret and Apply Specifications and Manual','Reading, Interpreting and Applying Specifications and Manual','common',4,NULL,'Identify/access manuals and interpret data and specifications; Apply information accessed in manual; Store manual.',4,'active','2025-12-04 13:41:47'),(133,8,'DRV-C04',4,'Use and apply lubricant/coolant','Using and applying lubricant/coolant','common',3,NULL,'Identify types of lubricant/coolant; Use and apply lubricant/coolant.',4,'active','2025-12-04 13:41:47'),(134,8,'DRV-C05',5,'Perform Mensuration and Calculation','Performing Mensuration and Calculation','common',3,NULL,'Inspect/clean tools and shop equipment; Store/arrange tools and equipment; Dispose waste/used lubricants; Report damaged tools/equipment.',4,'active','2025-12-04 13:41:47'),(135,8,'DRV-C06',6,'Move and Position Vehicle','Theoretical driving; Moving and positioning vehicle','common',20,NULL,'Gain required knowledge for introduction to driving and relevant LTO regulations; Prepare vehicle for driving; Move and position vehicle; Check the vehicle.',4,'active','2025-12-04 13:41:47'),(136,8,'DRV-CORE01',1,'Carry out minor vehicle maintenance and servicing','Carrying out minor vehicle maintenance and servicing','core',30,NULL,'Clean vehicle unit; Maintain and service the vehicle system.',4,'active','2025-12-04 13:41:47'),(137,8,'DRV-CORE02',2,'Drive light vehicles','Driving light vehicles','core',30,NULL,'Perform light vehicle pre-starting and warm-up; Drive light vehicles; Monitor and maintain vehicle performance.',4,'active','2025-12-04 13:41:47'),(138,8,'DRV-CORE03',3,'Observe traffic rules and regulations','Observing traffic rules and regulations','core',10,NULL,'Observe traffic signs and road markers; Obey traffic rules and regulations; Practice courtesy; Respect traffic enforcers and other traffic management units.',4,'active','2025-12-04 13:41:47'),(139,8,'DRV-CORE04',4,'Implement and coordinate accident/emergency procedures','Implementing and coordinating accident/emergency procedures','core',10,NULL,'Respond to emergencies; Arrange follow-up support and assistance.',4,'active','2025-12-04 13:41:47');
/*!40000 ALTER TABLE `competencies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `course_assignments`
--

DROP TABLE IF EXISTS `course_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `course_assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `trainer_id` varchar(20) NOT NULL,
  `course_code` varchar(50) NOT NULL,
  `date_assigned` datetime DEFAULT current_timestamp(),
  `assigned_by` varchar(20) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_assignment` (`trainer_id`,`course_code`),
  KEY `course_code` (`course_code`),
  KEY `assigned_by` (`assigned_by`),
  CONSTRAINT `course_assignments_ibfk_1` FOREIGN KEY (`trainer_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `course_assignments_ibfk_2` FOREIGN KEY (`course_code`) REFERENCES `courses` (`course_code`),
  CONSTRAINT `course_assignments_ibfk_3` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `course_assignments`
--

LOCK TABLES `course_assignments` WRITE;
/*!40000 ALTER TABLE `course_assignments` DISABLE KEYS */;
/*!40000 ALTER TABLE `course_assignments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `course_batches`
--

DROP TABLE IF EXISTS `course_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `course_batches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_code` varchar(50) NOT NULL,
  `batch_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_by` varchar(20) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `trainer_id` varchar(50) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_batch` (`course_code`,`batch_name`),
  KEY `created_by` (`created_by`),
  KEY `trainer_id` (`trainer_id`),
  CONSTRAINT `course_batches_ibfk_1` FOREIGN KEY (`course_code`) REFERENCES `courses` (`course_code`),
  CONSTRAINT `course_batches_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`),
  CONSTRAINT `course_batches_ibfk_3` FOREIGN KEY (`trainer_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `course_batches`
--

LOCK TABLES `course_batches` WRITE;
/*!40000 ALTER TABLE `course_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `course_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `course_materials`
--

DROP TABLE IF EXISTS `course_materials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `course_materials` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_code` varchar(50) NOT NULL,
  `competency_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content_type` enum('pdf','video','text','quiz','assignment','assessment') NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `competency_type` enum('basic','common','core') NOT NULL,
  `activity_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`activity_data`)),
  `is_locked` tinyint(1) DEFAULT 0,
  `access_code` varchar(100) DEFAULT NULL,
  `randomize_questions` tinyint(1) DEFAULT 0,
  `prevent_sharing` tinyint(1) DEFAULT 0,
  `date_created` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `course_code` (`course_code`),
  KEY `competency_id` (`competency_id`),
  CONSTRAINT `course_materials_ibfk_1` FOREIGN KEY (`course_code`) REFERENCES `courses` (`course_code`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `course_materials_ibfk_2` FOREIGN KEY (`competency_id`) REFERENCES `competencies` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `course_materials`
--

LOCK TABLES `course_materials` WRITE;
/*!40000 ALTER TABLE `course_materials` DISABLE KEYS */;
/*!40000 ALTER TABLE `course_materials` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `course_topics`
--

DROP TABLE IF EXISTS `course_topics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `course_topics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_code` varchar(50) NOT NULL,
  `competency_id` varchar(255) NOT NULL,
  `topic_name` varchar(255) NOT NULL,
  `topic_description` text DEFAULT NULL,
  `learning_objectives` text DEFAULT NULL,
  `added_by` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `course_code` (`course_code`),
  KEY `added_by` (`added_by`),
  CONSTRAINT `course_topics_ibfk_1` FOREIGN KEY (`course_code`) REFERENCES `courses` (`course_code`),
  CONSTRAINT `course_topics_ibfk_2` FOREIGN KEY (`added_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `course_topics`
--

LOCK TABLES `course_topics` WRITE;
/*!40000 ALTER TABLE `course_topics` DISABLE KEYS */;
/*!40000 ALTER TABLE `course_topics` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `courses`
--

DROP TABLE IF EXISTS `courses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `courses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_name` varchar(255) NOT NULL,
  `competency_name` varchar(255) NOT NULL,
  `module_title` varchar(100) NOT NULL,
  `course_code` varchar(50) NOT NULL,
  `hours` int(11) NOT NULL,
  `nominal_hours` int(11) unsigned NOT NULL,
  `description` text DEFAULT NULL,
  `learning_outcomes` mediumtext NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `status` enum('active','archived') DEFAULT 'active',
  `course_status` enum('draft','published','archived') DEFAULT 'draft',
  `allow_preview` tinyint(1) DEFAULT 0,
  `preview_content` text DEFAULT NULL,
  `require_verification` tinyint(1) DEFAULT 0,
  `verification_type` enum('email','student_id','phone') DEFAULT 'email',
  `date_created` datetime DEFAULT current_timestamp(),
  `branch_id` int(11) DEFAULT NULL,
  `schedule_days_per_week` int(11) DEFAULT NULL,
  `schedule_days` text DEFAULT NULL,
  `session_hours` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `course_code` (`course_code`),
  KEY `idx_courses_verification` (`require_verification`,`verification_type`),
  KEY `idx_courses_status` (`course_status`),
  KEY `idx_courses_branch` (`branch_id`),
  KEY `idx_courses_module_title` (`module_title`),
  KEY `idx_courses_competency_name` (`competency_name`),
  KEY `idx_courses_nominal_hours` (`nominal_hours`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `courses`
--

LOCK TABLES `courses` WRITE;
/*!40000 ALTER TABLE `courses` DISABLE KEYS */;
INSERT INTO `courses` (`id`, `course_name`, `competency_name`, `module_title`, `course_code`, `hours`, `nominal_hours`, `description`, `learning_outcomes`, `image`, `status`, `course_status`, `allow_preview`, `preview_content`, `require_verification`, `verification_type`, `date_created`, `branch_id`, `schedule_days_per_week`, `schedule_days`, `session_hours`) VALUES (1,'HAIRDRESSING','Hairdressing','National Certificate Level II','HRD-NCII',541,541,'This course provides knowledge, practical skills and attitude to perform pre and post hair care activities, hair and scalp treatment, basic hair coloring, hair bleaching, basic hair perming, hair straightening and basic haircutting, with classroom learning and practical work in actual or simulated sites.','Learners communicate effectively in the workplace, work in teams, solve routine problems, make career and life decisions, contribute to innovation, present relevant information, practice OSH, apply sustainable practices, and demonstrate entrepreneurial skills; They perform pre/post hair care, hair/scalp treatment, basic hair coloring and bleaching, perming, straightening, and basic haircutting to industry standards.',NULL,'active','published',0,NULL,0,'email','2025-12-04 13:41:46',NULL,NULL,NULL,NULL),(2,'AUTOMOTIVE SERVICING','Automotive Servicing','National Certificate Level I','AUT-NCI',488,488,'This course enhances knowledge, skills and attitudes in automotive servicing according to industry standards, including basic engine servicing for gas and diesel engines through removal and re-installation of components. It also strengthens the basic and common competencies required in the field.','Learners receive/respond to workplace communication; work with others; solve routine problems; enhance self-management; support innovation; access/maintain information; follow OSH; apply environmental work standards; adopt entrepreneurial mindset. They validate vehicle specifications; move and position vehicles; utilize automotive tools; perform mensuration and calculation; utilize workshop facilities and equipment; prepare servicing parts and consumables; prepare vehicles for servicing and releasing. They perform pre-delivery inspection; periodic maintenance of automotive engine; drive train; brake system; suspension system; and steering system.',NULL,'active','published',0,NULL,0,'email','2025-12-04 13:41:47',NULL,NULL,NULL,NULL),(3,'JAPANESE LANGUAGE AND CULTURE','Japanese Language and Culture','NTR','JPN-LC',350,350,'This course provides basic knowledge, attitudes and skills in understanding and communicating the Japanese language for Filipinos intending to go to Japan for training, tour or other purposes, including competencies on Japanese culture, writing system, grammar, counting system, expressions, and ordinary conversations.','Learners gain foundations in Japanese culture and work ethics; practice writing systems (Hiragana, Katakana, basic Kanji); master sounds and pronunciation; apply grammar, particles, conjunctions, demonstratives, verbs and adjectives in sentence construction; use counters and honorifics; and participate effectively in ordinary conversations.',NULL,'active','published',0,NULL,0,'email','2025-12-04 13:41:47',NULL,NULL,NULL,NULL),(4,'Dressmaking NC II','Dressmaking','National Certificate Level II','DRS-NCII',294,294,'This course enhances the knowledge, skills and attitudes of trainees in dressmaking according to industry standards, covering basic, common and core competencies including drafting patterns, cutting materials, sewing casual apparel, and applying finishing touches.','Learners participate in workplace communication, work in teams, practice career professionalism, and follow occupational health and safety; They carry out measurements and calculations, apply quality standards, perform basic maintenance, and set up and operate machines; They draft and cut patterns for casual apparel, prepare and cut materials, sew casual apparel, and apply finishing touches to meet industry requirements.',NULL,'active','published',0,NULL,0,'email','2025-12-04 13:41:47',NULL,NULL,NULL,NULL),(5,'BREAD AND PASTRY PRODUCTION','Bread and Pastry Production','National Certificate Level II','BPP-NCII',160,160,'This course enhances knowledge, skills and attitudes to clean equipment, tools and utensils and to prepare, portion, plate and serve pastries, breads and other dessert items for guests in hospitality and related operations.','Learners complete basic workplace competencies; observe hygiene; perform computer operations; practice workplace safety; deliver effective customer service; and produce bakery, pastry, cakes, petits fours, and other desserts to industry standards.',NULL,'active','published',0,NULL,0,'email','2025-12-04 13:41:47',NULL,NULL,NULL,NULL),(6,'Tailoring NC II','Tailoring','National Certificate Level II','TLR-NCII',294,294,'This course enhances knowledge, skills and attitudes in tailoring in accordance with industry standards, covering basic, common and core competencies including drafting and cutting patterns, preparing and cutting materials, sewing casual apparel, and applying finishing touches.','Learners complete basic workplace competencies; carry out measurements and calculations; apply quality standards; perform basic maintenance; set up and operate machines; and perform core tailoring tasks such as drafting and cutting patterns, preparing and cutting materials, sewing casual apparel, and finishing garments.',NULL,'active','published',0,NULL,0,'email','2025-12-04 13:41:47',NULL,NULL,NULL,NULL),(7,'AGRICULTURAL CROPS PRODUCTION','Agricultural Crops Production','National Certificate Level II','ACP-NCII',355,355,'This course enhances knowledge, desirable attitudes and skills for nursery operations, planting crops, caring and maintaining crops, and carrying out harvest and postharvest operations, including workplace communication, teamwork, safety in tools and equipment, housekeeping, and processing of farm wastes.','Learners complete basic competencies (communication, teamwork, problem-solving, career and life decisions, innovation, presenting information, OSH, sustainability, and entrepreneurship); common competencies (safety measures, farm tools and equipment, estimation and calculation, processing farm wastes, record keeping); and core competencies (nursery operations, planting crops, caring and maintaining crops, harvest and postharvest operations).',NULL,'active','published',0,NULL,0,'email','2025-12-04 13:41:47',NULL,NULL,NULL,NULL),(8,'DRIVING NC II','Driving','National Certificate Level II','DRV-NCII',154,154,'This course equips individuals with the attitudes, knowledge and skills required to perform tasks of a light vehicle driver per industry standards and regulatory requirements (LTO and other agencies), covering core competencies on vehicle maintenance and servicing, driving light vehicles, observing traffic rules and regulations, and implementing and coordinating accident/emergency procedures, alongside basic and common workplace competencies.','Learners complete basic workplace competencies; apply sealants/adhesives; perform shop maintenance and mensuration/calculation; read, interpret and apply specifications and manuals; use and apply lubricants/coolants; perform tool handling and shop upkeep; and move and position vehicles including theoretical driving. They carry out minor vehicle maintenance and servicing, drive light vehicles, observe traffic rules and regulations, and implement and coordinate accident/emergency procedures.',NULL,'active','published',0,NULL,0,'email','2025-12-04 13:41:47',NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `courses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `enrollments`
--

DROP TABLE IF EXISTS `enrollments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `enrollments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `trainee_id` varchar(20) NOT NULL,
  `course_code` varchar(50) NOT NULL,
  `course_name` varchar(255) NOT NULL,
  `batch_name` varchar(100) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `remarks` text DEFAULT NULL,
  `student_id_verification` varchar(50) DEFAULT NULL,
  `email_verification` varchar(255) DEFAULT NULL,
  `verification_status` enum('pending','verified','rejected') DEFAULT 'pending',
  `date_requested` datetime DEFAULT current_timestamp(),
  `processed_date` datetime DEFAULT NULL,
  `processed_by` varchar(20) DEFAULT NULL,
  `progress_percentage` decimal(5,2) DEFAULT 0.00,
  `completion_date` datetime DEFAULT NULL,
  `final_grade` decimal(5,2) DEFAULT NULL,
  `drop_date` datetime DEFAULT NULL,
  `drop_reason` text DEFAULT NULL,
  `dropped_by` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `trainee_id` (`trainee_id`),
  KEY `course_code` (`course_code`),
  KEY `processed_by` (`processed_by`),
  KEY `idx_enrollments_status` (`status`),
  KEY `idx_enrollments_progress` (`progress_percentage`),
  KEY `idx_enrollments_verification_status` (`verification_status`),
  CONSTRAINT `enrollments_ibfk_1` FOREIGN KEY (`trainee_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `enrollments_ibfk_2` FOREIGN KEY (`course_code`) REFERENCES `courses` (`course_code`),
  CONSTRAINT `enrollments_ibfk_3` FOREIGN KEY (`processed_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `enrollments`
--

LOCK TABLES `enrollments` WRITE;
/*!40000 ALTER TABLE `enrollments` DISABLE KEYS */;
/*!40000 ALTER TABLE `enrollments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `grades`
--

DROP TABLE IF EXISTS `grades`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `grades` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `trainee_id` varchar(50) NOT NULL,
  `course_code` varchar(50) NOT NULL,
  `activity_id` int(11) DEFAULT NULL,
  `quiz_id` int(11) DEFAULT NULL,
  `grade_type` enum('activity','quiz','assignment','final') NOT NULL,
  `score` decimal(5,2) NOT NULL,
  `max_score` int(11) NOT NULL,
  `percentage` decimal(5,2) DEFAULT NULL,
  `letter_grade` varchar(2) DEFAULT NULL,
  `graded_by` varchar(50) DEFAULT NULL,
  `graded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `trainee_id` (`trainee_id`),
  KEY `course_code` (`course_code`),
  KEY `idx_grades_trainee_course` (`trainee_id`,`course_code`),
  KEY `idx_grade_type` (`grade_type`),
  KEY `idx_graded_date` (`graded_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grades`
--

LOCK TABLES `grades` WRITE;
/*!40000 ALTER TABLE `grades` DISABLE KEYS */;
/*!40000 ALTER TABLE `grades` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hour_credits`
--

DROP TABLE IF EXISTS `hour_credits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hour_credits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `trainee_id` varchar(20) NOT NULL,
  `course_code` varchar(50) NOT NULL,
  `credit_date` date NOT NULL,
  `hours` int(11) NOT NULL,
  `source` enum('schedule','manual') NOT NULL DEFAULT 'schedule',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_credit` (`trainee_id`,`course_code`,`credit_date`),
  KEY `idx_credits_user_course_date` (`trainee_id`,`course_code`,`credit_date`),
  KEY `fk_hour_credits_course` (`course_code`),
  CONSTRAINT `fk_hour_credits_course` FOREIGN KEY (`course_code`) REFERENCES `courses` (`course_code`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_hour_credits_user` FOREIGN KEY (`trainee_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hour_credits`
--

LOCK TABLES `hour_credits` WRITE;
/*!40000 ALTER TABLE `hour_credits` DISABLE KEYS */;
/*!40000 ALTER TABLE `hour_credits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quiz_analytics`
--

DROP TABLE IF EXISTS `quiz_analytics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quiz_analytics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quiz_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `correct_attempts` int(11) DEFAULT 0,
  `total_attempts` int(11) DEFAULT 0,
  `average_time` int(11) DEFAULT NULL,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_quiz_question` (`quiz_id`,`question_id`),
  KEY `quiz_id` (`quiz_id`),
  KEY `question_id` (`question_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quiz_analytics`
--

LOCK TABLES `quiz_analytics` WRITE;
/*!40000 ALTER TABLE `quiz_analytics` DISABLE KEYS */;
/*!40000 ALTER TABLE `quiz_analytics` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quiz_attempts`
--

DROP TABLE IF EXISTS `quiz_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quiz_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quiz_id` int(11) NOT NULL,
  `trainee_id` varchar(50) NOT NULL,
  `answers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`answers`)),
  `score` decimal(5,2) DEFAULT NULL,
  `max_score` int(11) DEFAULT NULL,
  `attempt_number` int(11) DEFAULT 1,
  `completed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `time_spent` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `quiz_id` (`quiz_id`),
  KEY `trainee_id` (`trainee_id`),
  KEY `idx_attempts_trainee_quiz` (`trainee_id`,`quiz_id`),
  CONSTRAINT `fk_quiz_attempts_quiz` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quiz_attempts`
--

LOCK TABLES `quiz_attempts` WRITE;
/*!40000 ALTER TABLE `quiz_attempts` DISABLE KEYS */;
/*!40000 ALTER TABLE `quiz_attempts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quiz_categories`
--

DROP TABLE IF EXISTS `quiz_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quiz_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_code` varchar(50) NOT NULL,
  `category_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_by` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `course_code` (`course_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quiz_categories`
--

LOCK TABLES `quiz_categories` WRITE;
/*!40000 ALTER TABLE `quiz_categories` DISABLE KEYS */;
/*!40000 ALTER TABLE `quiz_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quiz_feedback`
--

DROP TABLE IF EXISTS `quiz_feedback`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quiz_feedback` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `attempt_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `feedback_text` text DEFAULT NULL,
  `feedback_type` enum('correct','incorrect','partial') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `attempt_id` (`attempt_id`),
  KEY `question_id` (`question_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quiz_feedback`
--

LOCK TABLES `quiz_feedback` WRITE;
/*!40000 ALTER TABLE `quiz_feedback` DISABLE KEYS */;
/*!40000 ALTER TABLE `quiz_feedback` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quiz_questions`
--

DROP TABLE IF EXISTS `quiz_questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quiz_questions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quiz_id` int(11) DEFAULT NULL,
  `course_code` varchar(50) NOT NULL,
  `question_text` text NOT NULL,
  `question_type` enum('multiple_choice','true_false','short_answer','essay') NOT NULL,
  `options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`options`)),
  `correct_answer` text DEFAULT NULL,
  `points` int(11) DEFAULT 1,
  `difficulty` enum('easy','medium','hard') DEFAULT 'medium',
  `explanation` text DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `question_order` int(11) DEFAULT 0,
  `created_by` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `quiz_id` (`quiz_id`),
  KEY `course_code` (`course_code`),
  KEY `question_type` (`question_type`),
  KEY `difficulty` (`difficulty`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `fk_quiz_questions_quiz` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quiz_questions`
--

LOCK TABLES `quiz_questions` WRITE;
/*!40000 ALTER TABLE `quiz_questions` DISABLE KEYS */;
/*!40000 ALTER TABLE `quiz_questions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quiz_settings`
--

DROP TABLE IF EXISTS `quiz_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quiz_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quiz_id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `quiz_id` (`quiz_id`),
  KEY `setting_key` (`setting_key`),
  CONSTRAINT `fk_quiz_settings_quiz` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quiz_settings`
--

LOCK TABLES `quiz_settings` WRITE;
/*!40000 ALTER TABLE `quiz_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `quiz_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quizzes`
--

DROP TABLE IF EXISTS `quizzes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quizzes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_code` varchar(50) NOT NULL,
  `competency_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `time_limit` int(11) DEFAULT NULL,
  `max_attempts` int(11) DEFAULT 1,
  `passing_score` decimal(5,2) DEFAULT 70.00,
  `is_randomized` tinyint(1) DEFAULT 0,
  `show_correct_answers` tinyint(1) DEFAULT 1,
  `status` enum('draft','published','archived') DEFAULT 'draft',
  `created_by` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `course_code` (`course_code`),
  KEY `category_id` (`competency_id`),
  KEY `created_by` (`created_by`),
  KEY `idx_quiz_status` (`status`),
  CONSTRAINT `quizzes_ibfk_1` FOREIGN KEY (`course_code`) REFERENCES `courses` (`course_code`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `quizzes_ibfk_2` FOREIGN KEY (`competency_id`) REFERENCES `competencies` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quizzes`
--

LOCK TABLES `quizzes` WRITE;
/*!40000 ALTER TABLE `quizzes` DISABLE KEYS */;
/*!40000 ALTER TABLE `quizzes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `submissions`
--

DROP TABLE IF EXISTS `submissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `submissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `trainee_id` varchar(20) NOT NULL,
  `material_id` int(11) NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `score` decimal(5,2) DEFAULT NULL,
  `teacher_remarks` text DEFAULT NULL,
  `submitted_at` datetime DEFAULT current_timestamp(),
  `graded_at` datetime DEFAULT NULL,
  `graded_by` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `trainee_id` (`trainee_id`),
  KEY `material_id` (`material_id`),
  KEY `graded_by` (`graded_by`),
  CONSTRAINT `submissions_ibfk_1` FOREIGN KEY (`trainee_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `submissions_ibfk_2` FOREIGN KEY (`material_id`) REFERENCES `course_materials` (`id`),
  CONSTRAINT `submissions_ibfk_3` FOREIGN KEY (`graded_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `submissions`
--

LOCK TABLES `submissions` WRITE;
/*!40000 ALTER TABLE `submissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `submissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_backups`
--

DROP TABLE IF EXISTS `system_backups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_backups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `backup_type` enum('full','database','files') NOT NULL,
  `backup_path` varchar(500) NOT NULL,
  `backup_size` bigint(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` varchar(50) DEFAULT NULL,
  `status` enum('success','failed','in_progress') DEFAULT 'in_progress',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_backups`
--

LOCK TABLES `system_backups` WRITE;
/*!40000 ALTER TABLE `system_backups` DISABLE KEYS */;
/*!40000 ALTER TABLE `system_backups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `topic_activities`
--

DROP TABLE IF EXISTS `topic_activities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `topic_activities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `topic_id` int(11) DEFAULT NULL,
  `activity_title` varchar(255) DEFAULT NULL,
  `activity_description` text DEFAULT NULL,
  `activity_type` enum('assignment','quiz','project','discussion') DEFAULT NULL,
  `start_date` datetime DEFAULT NULL,
  `due_date` datetime DEFAULT NULL,
  `max_score` int(11) DEFAULT NULL,
  `max_attempts` int(11) DEFAULT 1,
  `created_by` varchar(50) DEFAULT NULL,
  `attachment_path` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `version` int(11) DEFAULT 1,
  `parent_activity_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `topic_activities_ibfk_1` (`topic_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `topic_activities_ibfk_1` FOREIGN KEY (`topic_id`) REFERENCES `course_topics` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `topic_activities_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `topic_activities`
--

LOCK TABLES `topic_activities` WRITE;
/*!40000 ALTER TABLE `topic_activities` DISABLE KEYS */;
/*!40000 ALTER TABLE `topic_activities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `topic_materials`
--

DROP TABLE IF EXISTS `topic_materials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `topic_materials` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `topic_id` int(11) DEFAULT NULL,
  `material_title` varchar(255) DEFAULT NULL,
  `material_description` text DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `uploaded_by` varchar(50) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `topic_materials_ibfk_1` (`topic_id`),
  KEY `uploaded_by` (`uploaded_by`),
  CONSTRAINT `topic_materials_ibfk_1` FOREIGN KEY (`topic_id`) REFERENCES `course_topics` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `topic_materials_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `topic_materials`
--

LOCK TABLES `topic_materials` WRITE;
/*!40000 ALTER TABLE `topic_materials` DISABLE KEYS */;
/*!40000 ALTER TABLE `topic_materials` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_verification`
--

DROP TABLE IF EXISTS `user_verification`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_verification` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(50) NOT NULL,
  `verification_type` enum('email','student_id','phone') NOT NULL,
  `verification_code` varchar(100) DEFAULT NULL,
  `verification_data` varchar(255) DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_verification`
--

LOCK TABLES `user_verification` WRITE;
/*!40000 ALTER TABLE `user_verification` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_verification` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_verification_history`
--

DROP TABLE IF EXISTS `user_verification_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_verification_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(50) NOT NULL,
  `verification_type` enum('email','student_id','phone') NOT NULL,
  `verification_method` varchar(100) DEFAULT NULL,
  `verified_by` varchar(50) DEFAULT NULL,
  `verified_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('verified','rejected','expired') NOT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `verification_type` (`verification_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_verification_history`
--

LOCK TABLES `user_verification_history` WRITE;
/*!40000 ALTER TABLE `user_verification_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_verification_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(20) NOT NULL,
  `role` enum('admin','trainer','trainee','guest') NOT NULL,
  `branch_id` int(11) DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `suffix` varchar(10) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified` tinyint(1) DEFAULT 0,
  `student_id` varchar(50) DEFAULT NULL,
  `phone_verified` tinyint(1) DEFAULT 0,
  `contact_number` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `force_password_change` tinyint(1) DEFAULT 1,
  `status` text DEFAULT 'active',
  `date_created` datetime DEFAULT current_timestamp(),
  `last_login` datetime DEFAULT NULL,
  `password_changed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_users_email_verified` (`email_verified`),
  KEY `idx_users_student_id` (`student_id`),
  KEY `idx_users_branch` (`branch_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` (`id`, `user_id`, `role`, `branch_id`, `first_name`, `middle_name`, `last_name`, `suffix`, `email`, `email_verified`, `student_id`, `phone_verified`, `contact_number`, `password`, `profile_picture`, `force_password_change`, `status`, `date_created`, `last_login`, `password_changed_at`) VALUES (1,'11204345','admin',NULL,'Admin',NULL,'User',NULL,'admin@bts.gov.ph',1,NULL,0,NULL,'$2y$10$XKt00Jt3bRURLBArm27/XenG2oolNo8PnYolS87515FuOJgcP6hLm',NULL,1,'active','2025-12-04 13:41:47','2025-12-04 13:42:34',NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `verification_tokens`
--

DROP TABLE IF EXISTS `verification_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `verification_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(50) NOT NULL,
  `token_type` enum('email','phone','student_id') NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `token` (`token`),
  KEY `expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `verification_tokens`
--

LOCK TABLES `verification_tokens` WRITE;
/*!40000 ALTER TABLE `verification_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `verification_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'elms_bts'
--

--
-- Dumping routines for database 'elms_bts'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-12-04 21:42:56
