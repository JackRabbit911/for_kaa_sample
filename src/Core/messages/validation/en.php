<?php

return array
(
    'filter'        => array(
        '257'           => 'Это поле допускает только целые числа',
        '257-options'   => 'Это поле допускает только целые числа от min_range до max_range',
        '274'           => 'The entered data does not match the email format',
    ),
    
    'required'      => 'This field is required',
    'email'         => 'The entered data does not match the email format',
                    
    'integer'       => 'This field only accepts whole numbers',
    'alpha'         => 'This field only accepts latin letters',
    'alpha_num'     => 'Это поле допускает только целые числа и латинские буквы',
    'alpha_space'   => 'This field only accepts latin letters and spaces',
    'alpha_utf8'    => 'Это поле допускает только буквы',
    'alpha_num_utf8' => 'Это поле допускает только целые числа и буквы',
    'alpha_space_utf8'=>'This field only accepts letters and spaces',
    'phone'         => 'Введенные данные не соответствуют формату номера телефона',
    'phone_strict'  => 'Введенные данные не соответствуют формату номера телефона',
    'valid_date'    => 'The entered data does not match the date format :format',
    'min_lenth'     => 'Длина строки должна быть не менее :min символов',
    'max_lenth'     => 'Длина строки должна быть не более :max символов',
    'lenth'         => 'Длина строки должна быть от :min до :max символов',
    'confirm'       => 'The entered data does not match the field :field',
    'regexp'        => 'The entered data contains invalid characters',
    
    'email-unique'  => 'User with this email is already registered',
    // 'pair'          => 'The login/password pair does not match',

    'required_one_of'=> 'One of the fields: (:fields) must be filled',
    'email_or_phone' => 'The entered data does not match the email or phone number format',
    
    'default'       => 'data entered incorrectly',
);

