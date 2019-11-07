<?php
return [
    [
        'class' => 'tachyon\Config',
        'singleton' => true
    ],
    [
        'class' => 'tachyon\components\Cookie',
        'singleton' => true,
        'properties' => [
            [
                'name' => 'duration',
                'value' => 30
            ]
        ]
    ],
    [
        'class' => 'tachyon\components\Encrypt',
        'singleton' => true,
        'properties' => [
            [
                'name' => 'algorithm',
                'value' => 'md5'
            ],
            [
                'name' => 'salt',
                'value' => 'sdnv5ln0vlz8nbl4emr'
            ]
        ]
    ],
    [
        'class' => 'tachyon\components\Csrf',
        'singleton' => true
    ],
    [
        'class' => 'tachyon\components\Flash',
        'singleton' => true
    ],
    [
        'class' => 'tachyon\cache\Output',
        'singleton' => true
    ],
    [
        'class' => 'tachyon\cache\Db',
        'singleton' => true
    ],
    [
        'class' => 'tachyon\components\Message',
        'singleton' => true
    ],
    [
        'class' => 'tachyon\components\Lang',
        'singleton' => true
    ],
    [
        'class' => 'tachyon\components\html\Html',
        'singleton' => true
    ],
    [
        'class' => 'tachyon\db\dataMapper\DbContext',
        'singleton' => true
    ],
    [
        'class' => 'tachyon\db\dbal\DbFactory',
        'singleton' => true
    ]
];