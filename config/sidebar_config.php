<?php
return [
    'role-applicant' => [
        ['component' => 'qa-forum',           'title' => 'Q&A Forum'],
        ['component' => 'z-score-checker',    'title' => 'Z-Score Checker'],
        ['component' => 'degree-programs',    'title' => 'Degree Programs'],
        ['component' => 'unicode-generator',  'title' => 'Unicode Generator'],
        ['component' => 'connect-undergrads', 'title' => 'Connect with Undergrads'],
        ['component' => 'announcements',      'title' => 'Announcements'],
    ],
    'role-undergrad' => [
        ['component' => 'qa-forum',        'title' => 'Q&A Forum'],
        ['component' => 'create-session',  'title' => 'Create Session'],
        ['component' => 'peer-learning',    'title' => 'Peer Learning'],
        ['component' => 'announcements',   'title' => 'Announcements'],
        ['component' => 'Moderation', 'title' => 'Moderation'],
    ],
    'role-profile' => [
        ['component' => 'qa-forum',        'title' => 'Q&A Forum'],
        ['component' => 'publish-events',  'title' => 'Publish Events'],
        ['component' => 'announcements',   'title' => 'Announcements'],
    ],
    'role-admin' => [
        ['component' => 'degree-programs-management', 'title' => 'Degree Programs'],
        ['component' => 'content-review-queue',       'title' => 'Content Review Queue'],
        ['component' => 'role-applications',          'title' => 'Role Applications'],
    ],
];