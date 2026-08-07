<?php

return [
    'title' => 'About Arta Leca',
    'lead' => 'We have produced expanded clay aggregate since 1996 on one principle: the number printed on the datasheet is the number that arrives on the truck.',

    'story_title' => 'How the company grew',
    'story_body' => 'Arta Leca began with a single rotary kiln line in the Mahmoudabad Industrial Zone, Qom. Capacity was expanded in three stages; today three lines supply 450,000 m³ a year across fractions from 0 to 20 mm. Export to the Persian Gulf states, Iraq and Central Asia began in 2013.',

    'approach_title' => 'How we think about the material',
    'approach_body' => 'Lightweight aggregate is not a commodity — it is an engineered material with declared parameters. The difference between a good consignment and a poor one shows up in grading spread and bulk-density drift, not in how the granules look. Our technical investment goes into narrowing exactly that spread.',

    'values' => [
        'consistency' => [
            'title' => 'Repeatability',
            'body' => 'Continuous control of kiln temperature and residence time, so the hundredth load matches the first.',
        ],
        'traceability' => [
            'title' => 'Traceability',
            'body' => 'Every consignment ships with a batch number, production date and its own certificate of analysis.',
        ],
        'support' => [
            'title' => 'Technical support',
            'body' => 'We work with the design team on grade selection and mix design before the order is placed.',
        ],
    ],

    'timeline_title' => 'Milestones',
    'timeline' => [
        ['year' => '1996', 'title' => 'Founded, first line commissioned', 'body' => 'Production starts with one rotary kiln and 90,000 m³ of annual capacity.'],
        ['year' => '2005', 'title' => 'In-house laboratory opened', 'body' => 'Grading, bulk density and water absorption testing brought on site.'],
        ['year' => '2010', 'title' => 'Second line commissioned', 'body' => 'Capacity raised to 240,000 m³ and coarse fractions added to the range.'],
        ['year' => '2013', 'title' => 'Export begins', 'body' => 'First export consignments shipped to Persian Gulf destinations.'],
        ['year' => '2018', 'title' => 'Management systems certified', 'body' => 'Quality and occupational safety management systems implemented.'],
        ['year' => '2022', 'title' => 'Third line commissioned', 'body' => 'Capacity reaches 450,000 m³ a year, with heat recovery from kiln exhaust gas.'],
    ],

    'quality_title' => 'Quality control',
    'quality_lead' => 'In lightweight aggregate, quality means predictability. Our test programme is built around that.',
    'quality_tests_title' => 'Routine testing',
    'quality_tests' => [
        ['name' => 'Particle size distribution (sieve)', 'frequency' => 'Every shift', 'standard' => 'ASTM C136 / ISIRI 4977'],
        ['name' => 'Loose bulk density', 'frequency' => 'Every shift', 'standard' => 'EN 1097-3'],
        ['name' => 'Water absorption, 24 h', 'frequency' => 'Daily', 'standard' => 'EN 1097-6'],
        ['name' => 'Crushing resistance', 'frequency' => 'Weekly', 'standard' => 'EN 13055-1'],
        ['name' => 'Thermal conductivity', 'frequency' => 'Quarterly', 'standard' => 'ISO 8301'],
        ['name' => 'Volume stability & harmful substances', 'frequency' => 'Quarterly', 'standard' => 'EN 1744-1'],
    ],
    'quality_process_title' => 'From sample to certificate',
    'quality_process' => [
        'Automatic sampling off the cooler at the start, middle and end of every shift.',
        'Grading and bulk density tested in the plant laboratory, recorded against the batch.',
        'The line is stopped and kiln parameters corrected whenever a result leaves the tolerance band.',
        'A certificate of analysis is issued for the consignment as it is loaded.',
        'A retained sample of every batch is kept for six months.',
    ],

    'plant_title' => 'Plant & production process',
    'plant_lead' => 'Expanded clay is made from clay — but what decides its quality is heat and time.',
    'plant_steps' => [
        ['step' => '01', 'title' => 'Extraction & homogenisation', 'body' => 'Clay is extracted from selected deposits and homogenised under cover to limit variation in the chemistry of the kiln feed.'],
        ['step' => '02', 'title' => 'Preparation & granulation', 'body' => 'Crushing, moisture adjustment and forming of the clay into green granules of controlled size.'],
        ['step' => '03', 'title' => 'Pre-heating', 'body' => 'Moisture is driven off gradually before the firing zone, so granules do not crack as they expand.'],
        ['step' => '04', 'title' => 'Rotary kiln firing', 'body' => 'Heating to roughly 1,200 °C. Released gases create the closed cellular structure and the granule expands to several times its original volume.'],
        ['step' => '05', 'title' => 'Controlled cooling', 'body' => 'Temperature is reduced in stages to lock in the structure; recovered heat feeds the pre-heater.'],
        ['step' => '06', 'title' => 'Grading & storage', 'body' => 'Multi-deck screens separate the standard fractions, which are stored in dedicated silos.'],
        ['step' => '07', 'title' => 'Packing & loading', 'body' => 'Loaded in bulk, 1 m³ big bags or 50-litre sacks, with the consignment certificate of analysis.'],
    ],
];
