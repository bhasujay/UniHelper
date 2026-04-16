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
        ['component' => 'announcements',   'title' => 'Feed'],
        ['component' => 'admin-panel',   'title' => 'Admin Panel'],
        ['component' => 'connections', 'title' => 'Connections'],
        ['component' => 'qa-forum',        'title' => 'Q&A Forum'],
        ['component' => 'moderation', 'title' => 'Moderation'],
    ],
];