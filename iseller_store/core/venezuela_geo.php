<?php
// Estados de Venezuela y sus municipios. Se usa en el onboarding y en la
// configuración de la tienda para los selects de estado / municipio.
function venezuela_estados_municipios(): array
{
    return [
        'Amazonas' => [
            'Alto Orinoco', 'Atabapo', 'Atures', 'Autana', 'Manapiare', 'Maroa', 'Río Negro',
        ],
        'Anzoátegui' => [
            'Anaco', 'Aragua', 'Bolívar', 'Bruzual', 'Cajigal', 'Carvajal', 'Diego Bautista Urbaneja',
            'Freites', 'Guanipa', 'Guanta', 'Independencia', 'Libertad', 'Miranda', 'Monagas',
            'Peñalver', 'Píritu', 'San Juan de Capistrano', 'Santa Ana', 'Simón Rodríguez',
            'Sir Arthur McGregor', 'Sotillo',
        ],
        'Apure' => [
            'Achaguas', 'Biruaca', 'Muñoz', 'Páez', 'Pedro Camejo', 'Rómulo Gallegos', 'San Fernando',
        ],
        'Aragua' => [
            'Alcántara', 'Bolívar', 'Camatagua', 'Girardot', 'Iragorry', 'Lamas', 'Libertador',
            'Mariño', 'Michelena', 'Ocumare de la Costa de Oro', 'Revenga', 'Ribas', 'San Casimiro',
            'San Sebastián', 'Sucre', 'Tovar', 'Urdaneta', 'Zamora',
        ],
        'Barinas' => [
            'Alberto Arvelo Torrealba', 'Andrés Eloy Blanco', 'Antonio José de Sucre', 'Arismendi',
            'Barinas', 'Bolívar', 'Cruz Paredes', 'Ezequiel Zamora', 'Obispos', 'Pedraza', 'Rojas', 'Sosa',
        ],
        'Bolívar' => [
            'Angostura', 'Angostura del Orinoco', 'Caroní', 'Cedeño', 'El Callao', 'Gran Sabana',
            'Padre Pedro Chien', 'Piar', 'Roscio', 'Sifontes', 'Sucre',
        ],
        'Carabobo' => [
            'Bejuma', 'Carlos Arvelo', 'Diego Ibarra', 'Guacara', 'Juan José Mora', 'Libertador',
            'Los Guayos', 'Miranda', 'Montalbán', 'Naguanagua', 'Puerto Cabello', 'San Diego',
            'San Joaquín', 'Valencia',
        ],
        'Cojedes' => [
            'Anzoátegui', 'Girardot', 'Lima Blanco', 'Pao de San Juan Bautista', 'Ricaurte',
            'Rómulo Gallegos', 'San Carlos', 'Tinaco', 'Tinaquillo',
        ],
        'Delta Amacuro' => [
            'Antonio Díaz', 'Casacoima', 'Pedernales', 'Tucupita',
        ],
        'Distrito Capital' => [
            'Libertador',
        ],
        'Falcón' => [
            'Acosta', 'Bolívar', 'Buchivacoa', 'Carirubana', 'Colina', 'Dabajuro', 'Democracia',
            'Falcón', 'Federación', 'Jacura', 'Los Taques', 'Manaure', 'Mauroa', 'Miranda',
            'Monseñor Iturriza', 'Palmasola', 'Petit', 'Píritu', 'San Francisco', 'Silva', 'Sucre',
            'Tocópero', 'Unión', 'Urumaco', 'Zamora',
        ],
        'Guárico' => [
            'Camaguán', 'Chaguaramas', 'El Socorro', 'Francisco de Miranda', 'José Félix Ribas',
            'José Tadeo Monagas', 'Juan Germán Roscio', 'Juan José Rondón', 'Julián Mellado',
            'Leonardo Infante', 'Ortiz', 'San Gerónimo de Guayabal', 'San José de Guaribe',
            'Santa María de Ipire', 'Zaraza',
        ],
        'Lara' => [
            'Andrés Eloy Blanco', 'Crespo', 'Iribarren', 'Jiménez', 'Morán', 'Palavecino',
            'Simón Planas', 'Torres', 'Urdaneta',
        ],
        'La Guaira' => [
            'Vargas',
        ],
        'Mérida' => [
            'Alberto Adriani', 'Andrés Bello', 'Antonio Pinto Salinas', 'Aricagua', 'Arzobispo Chacón',
            'Campo Elías', 'Caracciolo Parra Olmedo', 'Cardenal Quintero', 'Guaraque', 'Julio César Salas',
            'Justo Briceño', 'Libertador', 'Miranda', 'Obispo Ramos de Lora', 'Padre Noguera',
            'Pueblo Llano', 'Rangel', 'Rivas Dávila', 'Santos Marquina', 'Sucre', 'Tovar',
            'Tulio Febres Cordero', 'Zea',
        ],
        'Miranda' => [
            'Acevedo', 'Andrés Bello', 'Baruta', 'Bolívar', 'Brión', 'Buroz', 'Carrizal', 'Chacao',
            'Cristóbal Rojas', 'El Hatillo', 'Guaicaipuro', 'Gual', 'Independencia', 'Lander',
            'Los Salias', 'Páez', 'Paz Castillo', 'Plaza', 'Sucre', 'Urdaneta', 'Zamora',
        ],
        'Monagas' => [
            'Acosta', 'Aguasay', 'Bolívar', 'Caripe', 'Cedeño', 'Libertador', 'Maturín', 'Piar',
            'Punceres', 'Santa Bárbara', 'Sotillo', 'Uracoa', 'Zamora',
        ],
        'Nueva Esparta' => [
            'Antolín del Campo', 'Antonio Díaz', 'Arismendi', 'García', 'Gómez', 'Macanao', 'Maneiro',
            'Marcano', 'Mariño', 'Tubores', 'Villalba',
        ],
        'Portuguesa' => [
            'Agua Blanca', 'Araure', 'Esteller', 'Guanare', 'Guanarito', 'José Vicente de Unda',
            'Ospino', 'Páez', 'Papelón', 'San Genaro de Boconoíto', 'San Rafael de Onoto',
            'Santa Rosalía', 'Sucre', 'Turén',
        ],
        'Sucre' => [
            'Andrés Eloy Blanco', 'Andrés Mata', 'Arismendi', 'Benítez', 'Bermúdez', 'Bolívar',
            'Cajigal', 'Cruz Salmerón Acosta', 'Libertador', 'Mariño', 'Mejía', 'Montes', 'Ribero',
            'Sucre', 'Valdez',
        ],
        'Táchira' => [
            'Andrés Bello', 'Antonio Rómulo Costa', 'Ayacucho', 'Bolívar', 'Cárdenas', 'Córdoba',
            'Fernández Feo', 'Francisco de Miranda', 'García de Hevia', 'Guásimos', 'Independencia',
            'Jáuregui', 'José María Vargas', 'Junín', 'Libertad', 'Libertador', 'Lobatera', 'Michelena',
            'Panamericano', 'Pedro María Ureña', 'Rafael Urdaneta', 'Samuel Darío Maldonado',
            'San Cristóbal', 'San Judas Tadeo', 'Seboruco', 'Simón Rodríguez', 'Sucre', 'Torbes',
            'Uribante',
        ],
        'Trujillo' => [
            'Andrés Bello', 'Boconó', 'Bolívar', 'Candelaria', 'Carache', 'Carvajal', 'Escuque',
            'Juan Vicente Campo Elías', 'La Ceiba', 'Márquez Cañizales', 'Miranda', 'Monte Carmelo',
            'Motatán', 'Pampán', 'Pampanito', 'Rangel', 'Sucre', 'Trujillo', 'Urdaneta', 'Valera',
        ],
        'Yaracuy' => [
            'Arístides Bastidas', 'Bolívar', 'Bruzual', 'Cocorote', 'Independencia', 'José Antonio Páez',
            'La Trinidad', 'Manuel Monge', 'Nirgua', 'Peña', 'San Felipe', 'Sucre', 'Urachiche', 'Veroes',
        ],
        'Zulia' => [
            'Almirante Padilla', 'Baralt', 'Cabimas', 'Catatumbo', 'Colón', 'Francisco Javier Pulgar',
            'Guajira', 'Jesús Enrique Lossada', 'Jesús María Semprún', 'La Cañada de Urdaneta',
            'Lagunillas', 'Machiques de Perijá', 'Mara', 'Maracaibo', 'Miranda', 'Rosario de Perijá',
            'San Francisco', 'Santa Rita', 'Simón Bolívar', 'Sucre', 'Valmore Rodríguez',
        ],
    ];
}
