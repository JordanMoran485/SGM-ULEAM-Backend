<?php

return [
    'required' => 'El campo :attribute es obligatorio.',
    'email'    => 'El :attribute debe ser una dirección de correo válida.',
    'unique'   => 'Este :attribute ya ha sido registrado.',
    'confirmed' => 'La confirmación de la :attribute no coincide.',

    /*
    | Aquí es donde sucede la magia: traduces los nombres de los campos
    */
    'attributes' => [
        'email'    => 'correo electrónico',
        'password' => 'contraseña',
        'name'     => 'nombre',
        'lastname' => 'apellido',
        'facultad' => 'facultad',
    ],
];
