-- =======================================================
-- Student Placement Management System Database
-- Database Name: student_placement_system
-- Compatibility: MySQL 5.7+ / MariaDB 10.4+ / XAMPP
-- Sign-in is restricted to active users with role = 'admin'.
-- This setup drops and recreates the tables before adding demo data.
-- =======================================================

CREATE DATABASE IF NOT EXISTS `student_placement_system`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `student_placement_system`;

-- Disable foreign key checks during setup
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `contact_messages`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `placement_records`;
DROP TABLE IF EXISTS `interviews`;
DROP TABLE IF EXISTS `test_results`;
DROP TABLE IF EXISTS `coding_questions`;
DROP TABLE IF EXISTS `coding_tests`;
DROP TABLE IF EXISTS `aptitude_questions`;
DROP TABLE IF EXISTS `aptitude_tests`;
DROP TABLE IF EXISTS `applications`;
DROP TABLE IF EXISTS `placement_drives`;
DROP TABLE IF EXISTS `companies`;
DROP TABLE IF EXISTS `students`;
DROP TABLE IF EXISTS `users`;

-- -------------------------------------------------------
-- 1. Table: users
-- -------------------------------------------------------
CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(120) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('student', 'admin', 'recruiter') NOT NULL DEFAULT 'student',
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- 2. Table: students
-- -------------------------------------------------------
CREATE TABLE `students` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL UNIQUE,
    `register_no` VARCHAR(50) NOT NULL UNIQUE,
    `phone` VARCHAR(20) DEFAULT NULL,
    `dob` DATE DEFAULT NULL,
    `gender` ENUM('Male', 'Female', 'Other') DEFAULT 'Female',
    `address` TEXT DEFAULT NULL,
    `department` VARCHAR(100) NOT NULL,
    `year_of_study` INT NOT NULL DEFAULT 4,
    `semester` INT NOT NULL DEFAULT 8,
    `cgpa` DECIMAL(4,2) NOT NULL DEFAULT 0.00,
    `tenth_percentage` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `twelfth_percentage` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `backlogs` INT NOT NULL DEFAULT 0,
    `programming_languages` TEXT DEFAULT NULL,
    `technical_skills` TEXT DEFAULT NULL,
    `soft_skills` TEXT DEFAULT NULL,
    `certifications` TEXT DEFAULT NULL,
    `projects` TEXT DEFAULT NULL,
    `resume_path` VARCHAR(255) DEFAULT NULL,
    `profile_completed` TINYINT(1) DEFAULT 0,
    `placement_status` ENUM('Not Placed', 'In Process', 'Placed') DEFAULT 'Not Placed',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_students_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- 3. Table: companies
-- -------------------------------------------------------
CREATE TABLE `companies` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT DEFAULT NULL UNIQUE,
    `name` VARCHAR(150) NOT NULL,
    `email` VARCHAR(150) NOT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `website` VARCHAR(255) DEFAULT NULL,
    `logo` VARCHAR(255) DEFAULT NULL,
    `location` VARCHAR(150) DEFAULT NULL,
    `industry` VARCHAR(100) DEFAULT 'Information Technology',
    `description` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_companies_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- 4. Table: placement_drives
-- -------------------------------------------------------
CREATE TABLE `placement_drives` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `company_id` INT NOT NULL,
    `job_title` VARCHAR(150) NOT NULL,
    `job_role` VARCHAR(100) NOT NULL,
    `package_lpa` DECIMAL(5,2) NOT NULL,
    `job_location` VARCHAR(150) NOT NULL,
    `job_description` TEXT NOT NULL,
    `required_skills` TEXT DEFAULT NULL,
    `min_cgpa` DECIMAL(4,2) NOT NULL DEFAULT 6.00,
    `max_backlogs` INT NOT NULL DEFAULT 0,
    `min_tenth_percentage` DECIMAL(5,2) NOT NULL DEFAULT 60.00,
    `min_twelfth_percentage` DECIMAL(5,2) NOT NULL DEFAULT 60.00,
    `eligible_departments` VARCHAR(255) NOT NULL DEFAULT 'CSE, IT, ECE, EEE',
    `selection_process` TEXT DEFAULT NULL,
    `application_deadline` DATE NOT NULL,
    `drive_date` DATE DEFAULT NULL,
    `status` ENUM('active', 'upcoming', 'closed') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_drives_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- 5. Table: applications
-- -------------------------------------------------------
CREATE TABLE `applications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `drive_id` INT NOT NULL,
    `student_id` INT NOT NULL,
    `applied_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `current_stage` ENUM('Applied', 'Under Review', 'Shortlisted', 'Aptitude', 'Coding', 'Technical Interview', 'HR Interview', 'Selected', 'Rejected') DEFAULT 'Applied',
    `remarks` TEXT DEFAULT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_application` (`drive_id`, `student_id`),
    CONSTRAINT `fk_app_drive` FOREIGN KEY (`drive_id`) REFERENCES `placement_drives` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_app_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- 6. Table: aptitude_tests
-- -------------------------------------------------------
CREATE TABLE `aptitude_tests` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `drive_id` INT DEFAULT NULL,
    `title` VARCHAR(150) NOT NULL,
    `category` ENUM('Comprehensive', 'Quantitative Aptitude', 'Logical Reasoning', 'Verbal Ability', 'Data Interpretation') DEFAULT 'Comprehensive',
    `duration_minutes` INT NOT NULL DEFAULT 20,
    `total_marks` INT NOT NULL DEFAULT 20,
    `pass_marks` INT NOT NULL DEFAULT 10,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_apt_drive` FOREIGN KEY (`drive_id`) REFERENCES `placement_drives` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- 7. Table: aptitude_questions
-- -------------------------------------------------------
CREATE TABLE `aptitude_questions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `test_id` INT NOT NULL,
    `question_text` TEXT NOT NULL,
    `category` ENUM('Quantitative Aptitude', 'Logical Reasoning', 'Verbal Ability', 'Data Interpretation') NOT NULL,
    `option_a` VARCHAR(255) NOT NULL,
    `option_b` VARCHAR(255) NOT NULL,
    `option_c` VARCHAR(255) NOT NULL,
    `option_d` VARCHAR(255) NOT NULL,
    `correct_option` CHAR(1) NOT NULL,
    `marks` INT NOT NULL DEFAULT 1,
    `explanation` TEXT DEFAULT NULL,
    CONSTRAINT `fk_apt_questions_test` FOREIGN KEY (`test_id`) REFERENCES `aptitude_tests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- 8. Table: coding_tests
-- -------------------------------------------------------
CREATE TABLE `coding_tests` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `drive_id` INT DEFAULT NULL,
    `title` VARCHAR(150) NOT NULL,
    `duration_minutes` INT NOT NULL DEFAULT 45,
    `total_marks` INT NOT NULL DEFAULT 100,
    `pass_marks` INT NOT NULL DEFAULT 50,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_coding_drive` FOREIGN KEY (`drive_id`) REFERENCES `placement_drives` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- 9. Table: coding_questions
-- -------------------------------------------------------
CREATE TABLE `coding_questions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `test_id` INT NOT NULL,
    `title` VARCHAR(150) NOT NULL,
    `difficulty` ENUM('Easy', 'Medium', 'Hard') DEFAULT 'Medium',
    `description` TEXT NOT NULL,
    `input_format` TEXT NOT NULL,
    `output_format` TEXT NOT NULL,
    `constraints` TEXT NOT NULL,
    `sample_input` TEXT NOT NULL,
    `sample_output` TEXT NOT NULL,
    `test_cases_json` TEXT DEFAULT NULL,
    CONSTRAINT `fk_coding_questions_test` FOREIGN KEY (`test_id`) REFERENCES `coding_tests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- 10. Table: test_results
-- -------------------------------------------------------
CREATE TABLE `test_results` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL,
    `test_id` INT NOT NULL,
    `test_type` ENUM('aptitude', 'coding') NOT NULL,
    `total_questions` INT NOT NULL DEFAULT 0,
    `correct_answers` INT NOT NULL DEFAULT 0,
    `wrong_answers` INT NOT NULL DEFAULT 0,
    `score` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `percentage` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `status` ENUM('Passed', 'Failed') NOT NULL,
    `taken_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_test_results_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- 11. Table: interviews
-- -------------------------------------------------------
CREATE TABLE `interviews` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `application_id` INT NOT NULL,
    `round_name` VARCHAR(100) NOT NULL,
    `interview_date` DATE NOT NULL,
    `interview_time` TIME NOT NULL,
    `venue_or_link` VARCHAR(255) NOT NULL,
    `interviewer_name` VARCHAR(100) DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `status` ENUM('Scheduled', 'Completed', 'Cancelled') DEFAULT 'Scheduled',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_interview_app` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- 12. Table: placement_records
-- -------------------------------------------------------
CREATE TABLE `placement_records` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL,
    `company_id` INT NOT NULL,
    `drive_id` INT DEFAULT NULL,
    `job_role` VARCHAR(150) NOT NULL,
    `package_lpa` DECIMAL(5,2) NOT NULL,
    `offer_date` DATE NOT NULL,
    `offer_letter_path` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_placements_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_placements_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- 13. Table: notifications
-- -------------------------------------------------------
CREATE TABLE `notifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(200) NOT NULL,
    `message` TEXT NOT NULL,
    `type` ENUM('drive', 'test', 'shortlist', 'interview', 'selection', 'announcement') DEFAULT 'announcement',
    `target_role` ENUM('all', 'student', 'recruiter') DEFAULT 'all',
    `drive_id` INT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- 14. Table: contact_messages
-- -------------------------------------------------------
CREATE TABLE `contact_messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL,
    `register_no` VARCHAR(50) DEFAULT NULL,
    `subject` VARCHAR(200) NOT NULL,
    `message` TEXT NOT NULL,
    `status` ENUM('unread', 'replied') DEFAULT 'unread',
    `admin_reply` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- =======================================================
-- SAMPLE DATA INSERTIONS
-- =======================================================

-- Demo administrator password: Admin@123

-- 1. Insert Users
INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `status`) VALUES
(1, 'Dr. K. Placement Officer', 'admin@college.edu', '$2y$10$7du7WaiAlgMVkc36Is4N4.KG8sHyi8eN7.AUXW11D0vlhpzURkZ2a', 'admin', 'active'),
(2, 'Google Campus Talent', 'recruiter@google.com', '$2y$10$H8GVPz9UaJWF/JkXE/14W.BzAFlgOTxX5RcM0G.kGSpTmbtUF/lcC', 'recruiter', 'active'),
(3, 'TCS Talent Acquisition', 'recruiter@tcs.com', '$2y$10$H8GVPz9UaJWF/JkXE/14W.BzAFlgOTxX5RcM0G.kGSpTmbtUF/lcC', 'recruiter', 'active'),
(4, 'Microsoft University Hiring', 'recruiter@microsoft.com', '$2y$10$H8GVPz9UaJWF/JkXE/14W.BzAFlgOTxX5RcM0G.kGSpTmbtUF/lcC', 'recruiter', 'active'),
(5, 'S. Priyadharshini', 'priya@student.edu', '$2y$10$keamLK3OFKnMpPBpySEJc.r0zqFWO7iq1kRaTGOA8T.FFgrzSBbBu', 'student', 'active'),
(6, 'Rahul Sharma', 'rahul@student.edu', '$2y$10$keamLK3OFKnMpPBpySEJc.r0zqFWO7iq1kRaTGOA8T.FFgrzSBbBu', 'student', 'active'),
(7, 'Ananya Verma', 'ananya@student.edu', '$2y$10$keamLK3OFKnMpPBpySEJc.r0zqFWO7iq1kRaTGOA8T.FFgrzSBbBu', 'student', 'active'),
(8, 'Karthik Raja', 'karthik@student.edu', '$2y$10$keamLK3OFKnMpPBpySEJc.r0zqFWO7iq1kRaTGOA8T.FFgrzSBbBu', 'student', 'active');

-- 2. Insert Students
INSERT INTO `students` (`id`, `user_id`, `register_no`, `phone`, `dob`, `gender`, `address`, `department`, `year_of_study`, `semester`, `cgpa`, `tenth_percentage`, `twelfth_percentage`, `backlogs`, `programming_languages`, `technical_skills`, `soft_skills`, `certifications`, `projects`, `resume_path`, `profile_completed`, `placement_status`) VALUES
(1, 5, '717821P101', '9876543210', '2003-05-14', 'Female', '45 College Road, Coimbatore, Tamil Nadu', 'CSE', 4, 8, 8.85, 92.50, 89.00, 0, 'Java, Python, C++, SQL', 'Spring Boot, MySQL, REST APIs, Git, React Basics', 'Problem Solving, Team Leadership, Adaptability', 'AWS Certified Cloud Practitioner, Oracle Java Associate', 'Placement Portal System, IoT Based Smart Farming', 'sample_resume.pdf', 1, 'In Process'),
(2, 6, '717821P102', '9876543211', '2003-08-22', 'Male', '12 Gandhi Street, Madurai, Tamil Nadu', 'IT', 4, 8, 7.90, 88.00, 85.50, 0, 'Python, JavaScript, PHP, MySQL', 'Django, Express, Docker Basics, HTML5/CSS3', 'Communication, Critical Thinking, Time Management', 'Google Data Analytics Professional', 'Hospital Appointment Booking System', NULL, 1, 'In Process'),
(3, 7, '717821P103', '9876543212', '2004-01-10', 'Female', '89 Park Avenue, Chennai, Tamil Nadu', 'ECE', 4, 8, 8.40, 95.00, 91.20, 0, 'C, C++, Embedded C, MATLAB', 'VLSI Design, Microcontrollers, Arduino, PCB Design', 'Presentation Skills, Analytical Mindset', 'Embedded Systems by Texas Instruments', 'Smart Traffic Light Controller with IoT', NULL, 1, 'Not Placed'),
(4, 8, '717821P104', '9876543213', '2003-11-05', 'Male', '23 Temple View, Trichy, Tamil Nadu', 'MECH', 4, 8, 6.70, 78.00, 72.00, 1, 'Python, SolidWorks, AutoCAD', 'ANSYS, Thermal Simulation, CNC Programming', 'Active Listening, Team Player', 'Autodesk Certified CAD Professional', 'Formula Student Chassis Design & Optimization', NULL, 1, 'Not Placed');

-- 3. Insert Companies
INSERT INTO `companies` (`id`, `user_id`, `name`, `email`, `phone`, `website`, `logo`, `location`, `industry`, `description`) VALUES
(1, 2, 'Google India', 'recruiter@google.com', '080-67218000', 'https://careers.google.com', 'google.png', 'Bengaluru, Hyderabad', 'Internet & Software', 'Google LLC is an American multinational technology company focusing on search engine technology, cloud computing, software, and AI.'),
(2, 3, 'Tata Consultancy Services (TCS)', 'recruiter@tcs.com', '022-67789999', 'https://www.tcs.com/careers', 'tcs.png', 'Chennai, Bengaluru, Pune', 'IT Services & Consulting', 'TCS is an Indian multinational information technology services and consulting company headquartered in Mumbai.'),
(3, 4, 'Microsoft Corporation', 'recruiter@microsoft.com', '080-40003000', 'https://careers.microsoft.com', 'microsoft.png', 'Hyderabad, Bengaluru', 'Computer Software & Cloud', 'Microsoft enables digital transformation for the era of an intelligent cloud and an intelligent edge.'),
(4, NULL, 'Amazon Development Center', 'recruiting-in@amazon.com', '080-41005000', 'https://amazon.jobs', 'amazon.png', 'Hyderabad, Chennai', 'E-Commerce & Cloud Computing', 'Amazon is guided by four principles: customer obsession, passion for invention, commitment to operational excellence, and long-term thinking.'),
(5, NULL, 'Zoho Corporation', 'careers@zohocorp.com', '044-67447070', 'https://www.zoho.com/careers', 'zoho.png', 'Chennai, Tenkasi', 'Cloud Software & SaaS', 'Zoho is a global tech company offering business software solutions built with a focus on privacy and long-term customer relationships.');

-- 4. Insert Placement Drives
INSERT INTO `placement_drives` (`id`, `company_id`, `job_title`, `job_role`, `package_lpa`, `job_location`, `job_description`, `required_skills`, `min_cgpa`, `max_backlogs`, `min_tenth_percentage`, `min_twelfth_percentage`, `eligible_departments`, `selection_process`, `application_deadline`, `drive_date`, `status`) VALUES
(1, 1, 'Software Engineer - University Graduate', 'Software Engineer', 18.50, 'Bengaluru / Hyderabad', 'We are looking for passionate entry-level software engineers to build massive-scale distributed systems, web architectures, and algorithms that power millions of users worldwide.', 'Data Structures, Algorithms, C++, Java, Python, OS Fundamentals', 8.00, 0, 80.00, 80.00, 'CSE, IT, ECE', 'Online Aptitude & Coding Test -> Technical Interview Round 1 -> Technical Interview Round 2 -> HR / Googliness Round', '2026-11-15', '2026-11-20', 'active'),
(2, 2, 'TCS Digital / Ninja Hiring Drive', 'Systems Engineer', 7.50, 'Chennai / Bengaluru / Pune', 'TCS National Qualifier Drive for engineering students. Selected candidates will work on cutting-edge technologies including Cloud, IoT, AI/ML, and Enterprise Applications.', 'C, Java, Python, SQL, Aptitude, Core Engineering', 6.50, 0, 60.00, 60.00, 'CSE, IT, ECE, EEE, MECH, CIVIL', 'TCS NQT Aptitude Test -> Technical Interview -> Managerial & HR Interview', '2026-11-10', '2026-11-18', 'active'),
(3, 3, 'Software Development Engineer (SDE-1)', 'Software Development Engineer', 21.00, 'Hyderabad', 'Microsoft Campus Recruitment for SDE-1. You will design, develop, test, and maintain cloud-native microservices and developer tools on Azure.', 'Algorithms, Data Structures, OOP, C# / C++ / Java, Problem Solving', 8.00, 0, 75.00, 75.00, 'CSE, IT', 'Online Assessment (Aptitude & Coding) -> System Design/DSA Tech Round -> Culture Fit & HR Interview', '2026-11-25', '2026-12-01', 'active'),
(4, 4, 'Cloud Support Associate', 'Cloud Associate', 9.50, 'Chennai / Bengaluru', 'Join Amazon Web Services (AWS) team to assist enterprise customers with cloud infrastructure, networking, Linux troubleshooting, and database performance.', 'Linux, Networking (TCP/IP), Python or Bash Scripting, Basic Cloud Concepts', 7.00, 0, 65.00, 65.00, 'CSE, IT, ECE, EEE', 'Online Aptitude Assessment -> Technical Discussion Round -> Bar Raiser Interview', '2026-11-12', '2026-11-22', 'active'),
(5, 5, 'Member Technical Staff (MTS)', 'Software Developer', 8.40, 'Chennai / Tenkasi', 'Zoho is hiring software craftspeople who love solving real-world challenges with clean, robust code without depending on excessive third-party bloat.', 'C, Java, Data Structures, Logic Building, Web Concepts', 6.50, 1, 60.00, 60.00, 'CSE, IT, ECE, EEE, MECH, CIVIL', 'Aptitude & Advanced Programming Round 1 -> Complex Problem Solving Round 2 -> HR Interview', '2026-11-30', '2026-12-05', 'active');

-- 5. Insert Applications
INSERT INTO `applications` (`id`, `drive_id`, `student_id`, `applied_at`, `current_stage`, `remarks`) VALUES
(1, 1, 1, '2026-10-01 10:15:00', 'Coding', 'Aptitude round cleared with distinction. Progressed to Coding Assessment.'),
(2, 2, 1, '2026-10-02 11:30:00', 'Technical Interview', 'Shortlisted for TCS Digital track based on high assessment marks.'),
(3, 2, 2, '2026-10-02 14:00:00', 'Aptitude', 'Application verified and approved. Aptitude test invitation sent.'),
(4, 4, 2, '2026-10-03 09:45:00', 'Shortlisted', 'Profile matched Cloud criteria. Awaiting online test schedule.'),
(5, 5, 3, '2026-10-04 16:20:00', 'Under Review', 'Application under review by Zoho campus hiring team.');

-- 6. Insert Aptitude Tests
INSERT INTO `aptitude_tests` (`id`, `drive_id`, `title`, `category`, `duration_minutes`, `total_marks`, `pass_marks`) VALUES
(1, NULL, 'General Campus Placement Aptitude Assessment', 'Comprehensive', 15, 12, 6),
(2, 1, 'Google University Aptitude & Logical Challenge', 'Logical Reasoning', 20, 10, 6),
(3, 2, 'TCS NQT Mock Aptitude Assessment', 'Comprehensive', 20, 10, 5);

-- 7. Insert Aptitude Questions
INSERT INTO `aptitude_questions` (`id`, `test_id`, `question_text`, `category`, `option_a`, `option_b`, `option_c`, `option_d`, `correct_option`, `marks`, `explanation`) VALUES
-- Test 1 (Comprehensive General Assessment)
(1, 1, 'A train running at the speed of 60 km/hr crosses a pole in 9 seconds. What is the length of the train?', 'Quantitative Aptitude', '120 metres', '180 metres', '324 metres', '150 metres', 'D', 1, 'Speed = 60 * (5/18) = 50/3 m/sec. Length = Speed * Time = (50/3) * 9 = 150 metres.'),
(2, 1, 'If the cost price of 20 articles is equal to the selling price of 16 articles, what is the profit percentage?', 'Quantitative Aptitude', '20%', '25%', '30%', '33.33%', 'B', 1, 'Profit % = (20 - 16)/16 * 100 = 4/16 * 100 = 25%.'),
(3, 1, 'A can do a work in 15 days and B in 20 days. If they work on it together for 4 days, then the fraction of the work that is left is:', 'Quantitative Aptitude', '7/15', '8/15', '1/10', '1/3', 'B', 1, 'Work in 4 days = 4 * (1/15 + 1/20) = 4 * (7/60) = 7/15. Remaining = 1 - 7/15 = 8/15.'),
(4, 1, 'Look at this series: 2, 1, (1/2), (1/4), ... What number should come next?', 'Logical Reasoning', '(1/3)', '(1/8)', '(2/8)', '(1/16)', 'B', 1, 'This is a simple division series; each number is one-half of the previous number: (1/4) / 2 = 1/8.'),
(5, 1, 'Pointing to a photograph of a boy Suresh said, "He is the son of the only son of my mother." How is Suresh related to that boy?', 'Logical Reasoning', 'Brother', 'Uncle', 'Cousin', 'Father', 'D', 1, 'The only son of Sureshs mother is Suresh himself. So the boy is Sureshs son, making Suresh his father.'),
(6, 1, 'Find the correctly spelt word:', 'Verbal Ability', 'Accomodate', 'Accommodate', 'Acommodate', 'Accomodete', 'B', 1, 'The correct spelling is ACCOMMODATE (with double c and double m).'),
(7, 1, 'Choose the word which is most nearly OPPOSITE in meaning to: CANDID', 'Verbal Ability', 'Blunt', 'Guarded', 'Sincere', 'Frank', 'B', 1, 'Candid means truthful and straightforward; guarded or secretive is its antonym.'),
(8, 1, 'Choose the appropriate preposition: The committee decided to abide _______ the majority decision.', 'Verbal Ability', 'with', 'by', 'to', 'for', 'B', 1, 'The idiomatic phrasal verb is to "abide by" a rule or decision.'),
(9, 1, 'If A : B = 2 : 3 and B : C = 4 : 5, then what is A : B : C?', 'Quantitative Aptitude', '8 : 12 : 15', '6 : 9 : 10', '2 : 4 : 5', '8 : 10 : 15', 'A', 1, 'Multiply A:B by 4 and B:C by 3 => A:B:C = 8:12:15.'),
(10, 1, 'A student scores 75, 80, 85, and 90 on four exams. What must she score on the fifth exam to average 85?', 'Quantitative Aptitude', '85', '90', '95', '100', 'C', 1, 'Total required = 85 * 5 = 425. Current sum = 75 + 80 + 85 + 90 = 330. Fifth score = 425 - 330 = 95.'),
(11, 1, 'Statements: All mangoes are golden in color. No golden-colored things are cheap. Conclusion: 1) All mangoes are cheap. 2) Golden-colored mangoes are not cheap.', 'Logical Reasoning', 'Only conclusion 1 follows', 'Only conclusion 2 follows', 'Either 1 or 2 follows', 'Neither follows', 'B', 1, 'Since all mangoes are golden and golden things are not cheap, golden mangoes cannot be cheap. Only 2 follows.'),
(12, 1, 'In a survey of 500 students, 60% like Coffee and 50% like Tea. If 20% like both, how many like neither?', 'Data Interpretation', '50', '60', '80', '100', 'A', 1, 'Percent liking either = 60 + 50 - 20 = 90%. Neither = 100 - 90 = 10%. 10% of 500 = 50 students.');

-- 8. Insert Coding Tests
INSERT INTO `coding_tests` (`id`, `drive_id`, `title`, `duration_minutes`, `total_marks`, `pass_marks`) VALUES
(1, 1, 'Google Core Algorithmic Coding Assessment', 45, 100, 60),
(2, NULL, 'Standard Campus Placement Technical Coding Test', 40, 100, 50);

-- 9. Insert Coding Questions
INSERT INTO `coding_questions` (`id`, `test_id`, `title`, `difficulty`, `description`, `input_format`, `output_format`, `constraints`, `sample_input`, `sample_output`, `test_cases_json`) VALUES
(1, 1, 'Two Sum - Target Pair Indices', 'Easy', 'Given an array of integers `nums` and an integer `target`, return indices of the two numbers such that they add up to `target`. You may assume that each input would have exactly one solution, and you may not use the same element twice. Return the indices separated by a space in ascending order.', 'First line contains space-separated integers representing the array.\nSecond line contains the target integer.', 'Two space-separated indices (0-indexed).', '2 <= nums.length <= 10^4\n-10^9 <= nums[i] <= 10^9\nOnly one valid answer exists.', '2 7 11 15\n9', '0 1', '[{"input": "2 7 11 15\\n9", "expected": "0 1"}, {"input": "3 2 4\\n6", "expected": "1 2"}, {"input": "3 3\\n6", "expected": "0 1"}]'),
(2, 1, 'Valid Parentheses Validator', 'Medium', 'Given a string `s` containing just the characters `(`, `)`, `{`, `}`, `[` and `]`, determine if the input string is valid.\nAn input string is valid if open brackets are closed by the same type of brackets, and open brackets are closed in the correct order.', 'A single line containing the string `s`.', 'Print `true` if the string is valid, otherwise print `false`.', '1 <= s.length <= 10^4\ns consists of parentheses only.', '()[]{}', 'true', '[{"input": "()[]{}", "expected": "true"}, {"input": "(]", "expected": "false"}, {"input": "([{}])", "expected": "true"}, {"input": "((()", "expected": "false"}]'),
(3, 2, 'Palindrome String Checker', 'Easy', 'A phrase is a palindrome if, after converting all uppercase letters into lowercase letters and removing all non-alphanumeric characters, it reads the same forward and backward. Given a string `s`, output `YES` if it is a palindrome, or `NO` otherwise.', 'A single line containing the string `s`.', '`YES` or `NO` (in capital letters).', '1 <= s.length <= 2 * 10^5', 'race a car', 'NO', '[{"input": "race a car", "expected": "NO"}, {"input": "A man a plan a canal Panama", "expected": "YES"}, {"input": "hello", "expected": "NO"}]');

-- 10. Insert Test Results (Sample completed tests)
INSERT INTO `test_results` (`id`, `student_id`, `test_id`, `test_type`, `total_questions`, `correct_answers`, `wrong_answers`, `score`, `percentage`, `status`, `taken_at`) VALUES
(1, 1, 1, 'aptitude', 12, 11, 1, 11.00, 91.67, 'Passed', '2026-10-01 11:00:00'),
(2, 1, 1, 'coding', 2, 2, 0, 100.00, 100.00, 'Passed', '2026-10-02 16:30:00'),
(3, 2, 1, 'aptitude', 12, 8, 4, 8.00, 66.67, 'Passed', '2026-10-03 14:15:00');

-- 11. Insert Interviews
INSERT INTO `interviews` (`id`, `application_id`, `round_name`, `interview_date`, `interview_time`, `venue_or_link`, `interviewer_name`, `notes`, `status`) VALUES
(1, 1, 'Technical Round 1 (System Design & Algorithms)', '2026-11-05', '10:30:00', 'https://meet.google.com/xyz-placement-tech', 'Arun Kumar (Senior SDE)', 'Focus on tree traversals, dynamic programming, and OOP concepts.', 'Scheduled'),
(2, 2, 'TCS Technical Discussion & Code Review', '2026-11-06', '14:00:00', 'Placement Cell Interview Room 2 / Microsoft Teams', 'Deepak N (Technical Lead)', 'Please bring 2 hard copies of updated resume.', 'Scheduled');

-- 12. Insert Placement Records
INSERT INTO `placement_records` (`id`, `student_id`, `company_id`, `drive_id`, `job_role`, `package_lpa`, `offer_date`, `offer_letter_path`) VALUES
(1, 1, 2, 2, 'Systems Engineer', 7.50, '2026-09-28', 'tcs_offer_priya.pdf');

-- 13. Insert Notifications
INSERT INTO `notifications` (`id`, `title`, `message`, `type`, `target_role`, `drive_id`, `created_at`) VALUES
(1, 'Google Campus Drive 2026 Announced!', 'Google India has opened registrations for University Graduate Software Engineers (18.5 LPA). Eligible departments: CSE, IT, ECE. Minimum CGPA: 8.00. Deadline: Nov 15, 2026.', 'drive', 'student', 1, '2026-10-01 09:00:00'),
(2, 'TCS Digital Recruitment Schedule Released', 'TCS National Qualifier Test mock assessments are now live on the portal. All registered applicants are advised to take the test before Nov 10.', 'test', 'student', 2, '2026-10-02 10:00:00'),
(3, 'Shortlisting Update for Microsoft SDE Drive', 'Applications for Microsoft SDE-1 will close on Nov 25. Ensure your resume and CGPA are updated in the profile section.', 'shortlist', 'student', 3, '2026-10-03 12:30:00'),
(4, 'Amazon Cloud Support Associate Drive Open', 'Amazon Development Center is hiring Cloud Support Associates (9.5 LPA). Selection involves Linux and Networking fundamentals.', 'drive', 'student', 4, '2026-10-04 15:00:00'),
(5, 'Campus Placement Preparation Workshop', 'Placement Cell is hosting an exclusive technical interview prep session on System Design and Dynamic Programming this Saturday in Auditorium 1.', 'announcement', 'all', NULL, '2026-10-05 08:30:00');

-- 14. Insert Contact Messages
INSERT INTO `contact_messages` (`id`, `name`, `email`, `register_no`, `subject`, `message`, `status`, `admin_reply`) VALUES
(1, 'S. Priyadharshini', 'priya@student.edu', '717821P101', 'Clarification regarding Microsoft SDE Online Assessment', 'Respected Sir, could you please confirm if the Microsoft assessment allows Python 3 alongside C++? Thank you.', 'replied', 'Yes Priya, Python 3 is fully supported in the Microsoft assessment platform.'),
(2, 'Rahul Sharma', 'rahul@student.edu', '717821P102', 'Update CGPA in portal database', 'Sir, my 7th semester revaluation result was published and my CGPA updated to 7.90. Please approve the change in portal.', 'unread', NULL);
