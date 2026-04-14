<?php
return [
    'role-applicant' => [
        ['component' => 'qa-forum',           'title' => 'Q&A Forum'],
        ['component' => 'z-score-checker',    'title' => 'Z-Score Checker'],
        ['component' => 'degree-programs',    'title' => 'Degree Programs'],
        ['component' => 'unicode-generator',  'title' => 'Unicode Generator'],
        ['component' => 'connections', 'title' => 'Connections'],
        ['component' => 'announcements',      'title' => 'Announcements'],
    ],
    'role-undergrad' => [
        ['component' => 'qa-forum',        'title' => 'Q&A Forum'],
        ['component' => 'peer-learning',    'title' => 'Peer Learning'],
        ['component' => 'connections', 'title' => 'Connections'],
        ['component' => 'announcements',   'title' => 'Announcements'],
        ['component' => 'moderation', 'title' => 'Moderation'],
    ],
    'role-profile' => [
        ['component' => 'qa-forum',        'title' => 'Q&A Forum'],
        ['component' => 'publish-events',  'title' => 'Publish Events'],
        ['component' => 'connections', 'title' => 'Connections'],
        ['component' => 'announcements',   'title' => 'Announcements'],
    ],
    'role-admin' => [
        ['component' => 'connections', 'title' => 'Connections'],
        ['component' => 'qa-forum',        'title' => 'Q&A Forum'],
        ['component' => 'moderation', 'title' => 'Moderation'],
    ],
];