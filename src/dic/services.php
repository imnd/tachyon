<?php
use tachyon\Config;
use tachyon\components\{
    Cookie, Encrypt, Csrf,
    Flash, Html, Message, Lang
};
use tachyon\cache\{
    Output,
    Db
};
use tachyon\db\{
    dataMapper\DbContext,
    dbal\DbFactory
};

return [
    [
        'class' => Db::class,
        'singleton' => true
    ],
    [
        'class' => DbContext::class,
        'singleton' => true
    ],
    [
        'class' => DbFactory::class,
        'singleton' => true
    ],
    [
        'class' => Config::class,
        'singleton' => true
    ],
    [
        'class' => Cookie::class,
        'singleton' => true,
        'properties' => [
            [
                'name' => 'duration',
                'value' => 30
            ]
        ]
    ],
    [
        'class' => Csrf::class,
        'singleton' => true
    ],
    [
        'class' => Encrypt::class,
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
        'class' => Flash::class,
        'singleton' => true
    ],
    [
        'class' => Html::class,
        'singleton' => true
    ],
    [
        'class' => Output::class,
        'singleton' => true
    ],
    [
        'class' => Lang::class,
        'singleton' => true
    ],
    [
        'class' => Message::class,
        'singleton' => true
    ],
];