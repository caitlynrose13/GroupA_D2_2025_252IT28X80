<?php
/**
 * Cybersecurity Awareness Program Structure
 * 
 * This file contains the fixed 12-month program structure that was previously
 * stored in database tables. Since the program structure is standardized and
 * doesn't change, using constants is more efficient than database queries.
 */

// Program Cycles (3 cycles of 4 months each)
define('PROGRAM_CYCLES', [
    1 => [
        'cycle_number' => 1,
        'title' => 'Foundational Threats and Digital Hygiene',
        'description' => 'Building strong security foundations with threat awareness and digital hygiene practices',
        'start_month' => 1,
        'end_month' => 4,
        'quiz_month' => 4,
        'focus' => 'Uses a mix of static and interactive resources to build a strong security foundation'
    ],
    2 => [
        'cycle_number' => 2,
        'title' => 'POPIA and Data Protection',
        'description' => 'Understanding and implementing data protection compliance requirements',
        'start_month' => 5,
        'end_month' => 8,
        'quiz_month' => 8,
        'focus' => 'Uses practical, document-based resources to help employees understand and comply with legal requirements'
    ],
    3 => [
        'cycle_number' => 3,
        'title' => 'Proactive Security and Advanced Threats',
        'description' => 'Advanced security practices and sophisticated threat response',
        'start_month' => 9,
        'end_month' => 12,
        'quiz_month' => 12,
        'focus' => 'Focuses on proactive security habits and tackling sophisticated threats, using resources that demand active engagement'
    ]
]);

// Program Months (12 months with topics, themes, and detailed resources)
define('PROGRAM_MONTHS', [
    1 => [
        'month_number' => 1,
        'cycle_id' => 1,
        'title' => 'Phishing and Social Engineering',
        'description' => 'Recognize and respond to phishing attacks and social engineering tactics',
        'theme' => 'Threat Recognition',
        'resource_type' => 'Poster',
        'resource_description' => 'A poster for common areas. This visually summarizes key red flags of phishing and social engineering attacks, serving as a constant reminder.',
        'learning_objectives' => [
            'Identify common phishing email characteristics',
            'Recognize social engineering tactics',
            'Know how to report suspicious communications',
            'Understand the importance of verification before sharing information'
        ]
    ],
    2 => [
        'month_number' => 2,
        'cycle_id' => 1,
        'title' => 'Password Management and Authentication',
        'description' => 'Strong password practices and multi-factor authentication setup',
        'theme' => 'Authentication Security',
        'resource_type' => 'Video',
        'resource_description' => 'A short, engaging video that visually demonstrates how to create a strong password and the step-by-step process of setting up multi-factor authentication (MFA).',
        'learning_objectives' => [
            'Create strong, unique passwords',
            'Understand password manager benefits',
            'Set up multi-factor authentication',
            'Recognize password security best practices'
        ]
    ],
    3 => [
        'month_number' => 3,
        'cycle_id' => 1,
        'title' => 'Malware and Ransomware',
        'description' => 'Understanding malware threats and ransomware prevention',
        'theme' => 'Malware Protection',
        'resource_type' => 'Newsletter',
        'resource_description' => 'A newsletter that provides a detailed breakdown of different types of malware and real-world examples of ransomware attacks, offering more depth than a poster or video.',
        'learning_objectives' => [
            'Identify different types of malware',
            'Understand ransomware attack vectors',
            'Know prevention and response strategies',
            'Recognize safe downloading practices'
        ]
    ],
    4 => [
        'month_number' => 4,
        'cycle_id' => 1,
        'title' => 'Cycle 1 Assessment',
        'description' => 'Assessment of foundational security knowledge',
        'theme' => 'Knowledge Assessment',
        'resource_type' => 'Quiz',
        'resource_description' => 'A quiz containing formative questions of the cycle\'s topics covering phishing, passwords, and malware.',
        'learning_objectives' => [
            'Demonstrate understanding of phishing threats',
            'Apply password security knowledge',
            'Identify malware prevention strategies',
            'Show foundational security awareness'
        ]
    ],
    5 => [
        'month_number' => 5,
        'cycle_id' => 2,
        'title' => 'POPIA Basics',
        'description' => 'Introduction to Protection of Personal Information Act requirements',
        'theme' => 'Legal Compliance',
        'resource_type' => 'Pamphlet/Guide',
        'resource_description' => 'A pamphlet or simple text-based guide that explains what personal information is and how POPIA affects their daily tasks in a clear, easy-to-read format.',
        'learning_objectives' => [
            'Understand what constitutes personal information',
            'Know POPIA compliance requirements',
            'Recognize data subject rights',
            'Apply POPIA principles in daily work'
        ]
    ],
    6 => [
        'month_number' => 6,
        'cycle_id' => 2,
        'title' => 'Data Handling and Compliance',
        'description' => 'Proper data handling procedures and compliance practices',
        'theme' => 'Data Management',
        'resource_type' => 'Infographic/Flowchart',
        'resource_description' => 'An infographic or flowchart that shows the POPIA 8 laws for data handling and processing.',
        'learning_objectives' => [
            'Follow proper data handling procedures',
            'Understand the 8 POPIA conditions',
            'Implement data processing safeguards',
            'Maintain compliance documentation'
        ]
    ],
    7 => [
        'month_number' => 7,
        'cycle_id' => 2,
        'title' => 'Physical Security and Data Privacy',
        'description' => 'Physical security measures and data privacy protection',
        'theme' => 'Physical Security',
        'resource_type' => 'Checklist',
        'resource_description' => 'A simple, printed checklist to be placed on desks or near exits, reminding employees of key actions like "lock your screen" and "shred confidential documents" at the end of the day.',
        'learning_objectives' => [
            'Implement physical security measures',
            'Secure workstations and documents',
            'Protect data in physical environments',
            'Follow end-of-day security procedures'
        ]
    ],
    8 => [
        'month_number' => 8,
        'cycle_id' => 2,
        'title' => 'Cycle 2 Assessment',
        'description' => 'Assessment of POPIA and data protection knowledge',
        'theme' => 'Knowledge Assessment',
        'resource_type' => 'Quiz',
        'resource_description' => 'A quiz containing formative questions of the cycle\'s topics covering POPIA compliance and data protection.',
        'learning_objectives' => [
            'Demonstrate POPIA compliance understanding',
            'Apply data handling best practices',
            'Show physical security awareness',
            'Validate data protection knowledge'
        ]
    ],
    9 => [
        'month_number' => 9,
        'cycle_id' => 3,
        'title' => 'Business Email Compromise and Financial Fraud',
        'description' => 'Advanced threat recognition and response procedures',
        'theme' => 'Advanced Threats',
        'resource_type' => 'Simulated Email',
        'resource_description' => 'A realistic, but harmless, simulated BEC email will be sent to employees to test their ability to spot and report this advanced type of scam.',
        'learning_objectives' => [
            'Identify business email compromise tactics',
            'Recognize financial fraud attempts',
            'Respond appropriately to suspicious requests',
            'Report advanced threats effectively'
        ]
    ],
    10 => [
        'month_number' => 10,
        'cycle_id' => 3,
        'title' => 'Secure Device and Network Usage',
        'description' => 'Advanced device security and network safety practices',
        'theme' => 'Network Security',
        'resource_type' => 'Video Demonstration',
        'resource_description' => 'A video that shows employees how to safely use public Wi-Fi, update software, and securely manage their work devices.',
        'learning_objectives' => [
            'Use public networks safely',
            'Maintain device security updates',
            'Implement secure remote work practices',
            'Protect data on mobile devices'
        ]
    ],
    11 => [
        'month_number' => 11,
        'cycle_id' => 3,
        'title' => 'Insider Threats and Threat Reporting',
        'description' => 'Identifying insider threats and establishing reporting procedures',
        'theme' => 'Threat Response',
        'resource_type' => 'Policy Email',
        'resource_description' => 'An email that outlines the company\'s clear and non-punitive policy on reporting security incidents, encouraging employees to be vigilant and act without fear.',
        'learning_objectives' => [
            'Recognize insider threat indicators',
            'Understand reporting procedures',
            'Create a culture of security awareness',
            'Report incidents without fear of punishment'
        ]
    ],
    12 => [
        'month_number' => 12,
        'cycle_id' => 3,
        'title' => 'Cycle 3 Assessment',
        'description' => 'Assessment of advanced security knowledge and practices',
        'theme' => 'Knowledge Assessment',
        'resource_type' => 'Quiz',
        'resource_description' => 'A quiz containing formative questions of the cycle\'s topics covering advanced threats and proactive security practices.',
        'learning_objectives' => [
            'Demonstrate advanced threat awareness',
            'Apply proactive security measures',
            'Show comprehensive security understanding',
            'Validate year-long learning progress'
        ]
    ]
]);

/**
 * Helper functions for program structure
 */

/**
 * Get cycle information by month number
 * @param int $month_number Month number (1-12)
 * @return array|null Cycle information or null if not found
 */
function getCycleByMonth($month_number) {
    $cycles = PROGRAM_CYCLES;
    foreach ($cycles as $cycle) {
        if ($month_number >= $cycle['start_month'] && $month_number <= $cycle['end_month']) {
            return $cycle;
        }
    }
    return null;
}

/**
 * Get month information by month number
 * @param int $month_number Month number (1-12)
 * @return array|null Month information or null if not found
 */
function getMonthInfo($month_number) {
    $months = PROGRAM_MONTHS;
    return isset($months[$month_number]) ? $months[$month_number] : null;
}

/**
 * Get all months in a specific cycle
 * @param int $cycle_number Cycle number (1-3)
 * @return array Array of month information
 */
function getMonthsByCycle($cycle_number) {
    $months = PROGRAM_MONTHS;
    $result = [];
    foreach ($months as $month) {
        if ($month['cycle_id'] == $cycle_number) {
            $result[] = $month;
        }
    }
    return $result;
}

/**
 * Check if a month is an assessment month
 * @param int $month_number Month number (1-12)
 * @return bool True if assessment month, false otherwise
 */
function isAssessmentMonth($month_number) {
    return in_array($month_number, [4, 8, 12]);
}

/**
 * Get the current program month based on date
 * This is a simple implementation - you may want to customize based on your program start date
 * @param DateTime|null $date Date to check (defaults to current date)
 * @return int Month number (1-12)
 */
function getCurrentProgramMonth($date = null) {
    if ($date === null) {
        $date = new DateTime();
    }
    
    // Simple calculation based on calendar month
    // You may want to adjust this based on your program start date
    $month = (int)$date->format('n');
    return $month;
}

/**
 * Validate month number
 * @param int $month_number Month number to validate
 * @return bool True if valid (1-12), false otherwise
 */
function isValidMonthNumber($month_number) {
    return is_numeric($month_number) && $month_number >= 1 && $month_number <= 12;
}