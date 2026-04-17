<?php
return [
    'role-applicant' => [
        ['component' => 'qa-forum',           'title' => 'Q&A Forum'],
        ['component' => 'z-score-checker',    'title' => 'Z-Score Checker'],
        ['component' => 'degree-programs',    'title' => 'Degree Programs'],
        ['component' => 'unicode-generator',  'title' => 'Unicode Generator'],
        ['component' => 'connections', 'title' => 'Connections'],
        ['component' => 'announcements',      'title' => 'Feed'],
    ],
    'role-undergrad' => [
        ['component' => 'qa-forum',        'title' => 'Q&A Forum'],
        ['component' => 'peer-learning',    'title' => 'Peer Learning'],
        ['component' => 'connections', 'title' => 'Connections'],
        ['component' => 'announcements',   'title' => 'Feed'],
        ['component' => 'moderation', 'title' => 'Moderation'],
    ],
    'role-profile' => [
        ['component' => 'qa-forum',        'title' => 'Q&A Forum'],
        ['component' => 'publish-events',  'title' => 'Publish Events'],
        ['component' => 'connections', 'title' => 'Connections'],
        ['component' => 'announcements',   'title' => 'Feed'],
    ],
    'role-admin' => [
        ['component' => 'admin-panel',   'title' => 'Admin Panel'],
        ['component' => 'user-management', 'title' => 'User Management'],
        ['component' => 'moderation', 'title' => 'Moderation'],
        ['component' => 'announcements',   'title' => 'Feed'],
        ['component' => 'feedback-forum', 'title' => 'User Feedbacks'],
        ['component' => 'qa-forum',        'title' => 'Q&A Forum'],
        ['component' => 'connections', 'title' => 'Connections'],
    ],
];