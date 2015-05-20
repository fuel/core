<?php

return array(
    
        'uncountable_words' => array(
                'equipment',
                'information',
                'rice',
                'money',
                'species',
                'series',
                'fish',
                'meta',
        ),
    
        'singular_rules' => array(
                '/(matr)ices$/i'         => '\1ix',
                '/(vert|ind)ices$/i'     => '\1ex',
                '/^(ox)en/i'             => '\1',
                '/(alias)es$/i'          => '\1',
                '/([octop|vir])i$/i'     => '\1us',
                '/(cris|ax|test)es$/i'   => '\1is',
                '/(shoe)s$/i'            => '\1',
                '/(o)es$/i'              => '\1',
                '/(bus|campus)es$/i'     => '\1',
                '/([m|l])ice$/i'         => '\1ouse',
                '/(x|ch|ss|sh)es$/i'     => '\1',
                '/(m)ovies$/i'           => '\1\2ovie',
                '/(s)eries$/i'           => '\1\2eries',
                '/([^aeiouy]|qu)ies$/i'  => '\1y',
                '/([lr])ves$/i'          => '\1f',
                '/(tive)s$/i'            => '\1',
                '/(hive)s$/i'            => '\1',
                '/([^f])ves$/i'          => '\1fe',
                '/(^analy)ses$/i'        => '\1sis',
                '/((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$/i' => '\1\2sis',
                '/([ti])a$/i'            => '\1um',
                '/(p)eople$/i'           => '\1\2erson',
                '/(m)en$/i'              => '\1an',
                '/(s)tatuses$/i'         => '\1\2tatus',
                '/(c)hildren$/i'         => '\1\2hild',
                '/(n)ews$/i'             => '\1\2ews',
                '/([^us])s$/i'           => '\1',
        ),
    
        'plural_rules' => array(
                '/^(ox)$/i'                 => '\1\2en',     // ox
                '/([m|l])ouse$/i'           => '\1ice',      // mouse, louse
                '/(matr|vert|ind)ix|ex$/i'  => '\1ices',     // matrix, vertex, index
                '/(x|ch|ss|sh)$/i'          => '\1es',       // search, switch, fix, box, process, address
                '/([^aeiouy]|qu)y$/i'       => '\1ies',      // query, ability, agency
                '/(hive)$/i'                => '\1s',        // archive, hive
                '/(?:([^f])fe|([lr])f)$/i'  => '\1\2ves',    // half, safe, wife
                '/sis$/i'                   => 'ses',        // basis, diagnosis
                '/([ti])um$/i'              => '\1a',        // datum, medium
                '/(p)erson$/i'              => '\1eople',    // person, salesperson
                '/(m)an$/i'                 => '\1en',       // man, woman, spokesman
                '/(c)hild$/i'               => '\1hildren',  // child
                '/(buffal|tomat)o$/i'       => '\1\2oes',    // buffalo, tomato
                '/(bu|campu)s$/i'           => '\1\2ses',    // bus, campus
                '/(alias|status|virus)$/i'  => '\1es',       // alias
                '/(octop)us$/i'             => '\1i',        // octopus
                '/(ax|cris|test)is$/i'      => '\1es',       // axis, crisis
                '/s$/'                      => 's',          // no change (compatibility)
                '/$/'                       => 's',
        ),

);
