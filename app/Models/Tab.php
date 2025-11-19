<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Tab extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'hackathon_id', 'title',
    ];

    public const TAB_TITLES = ['Обзор', 'Ресурсы', 'Правила', 'Контакты', 'Оценка', 'Награды'];

    public const DEFAULT_TRANSLATIONS = [
        'Обзор' => [
            'title' => [
                'en' => 'Overview',
                'de' => 'Übersicht',
                'fr' => 'Aperçu',
                'es' => 'Resumen',
                'zh_CN' => '概述',
                'pt_PT' => 'Visão Geral'
            ],
            'sections' => [
                'Описание' => [
                    'title' => [
                        'en' => 'Description',
                        'de' => 'Beschreibung',
                        'fr' => 'Description',
                        'es' => 'Descripción',
                        'zh_CN' => '描述',
                        'pt_PT' => 'Descrição'
                    ]
                ],
                'План проведения' => [
                    'title' => [
                        'en' => 'Event Schedule',
                        'de' => 'Veranstaltungsplan',
                        'fr' => 'Calendrier de l\'événement',
                        'es' => 'Calendario del evento',
                        'zh_CN' => '活动日程',
                        'pt_PT' => 'Programação do Evento'
                    ]
                ]
            ]
        ],
        'Ресурсы' => [
            'title' => [
                'en' => 'Resources',
                'de' => 'Ressourcen',
                'fr' => 'Ressources',
                'es' => 'Recursos',
                'zh_CN' => '资源',
                'pt_PT' => 'Recursos'
            ],
            'sections' => [
                'Ресурсы' => [
                    'title' => [
                        'en' => 'Resources',
                        'de' => 'Ressourcen',
                        'fr' => 'Ressources',
                        'es' => 'Recursos',
                        'zh_CN' => '资源',
                        'pt_PT' => 'Recursos'
                    ]
                ]
            ]
        ],
        'Правила' => [
            'title' => [
                'en' => 'Rules',
                'de' => 'Regeln',
                'fr' => 'Règles',
                'es' => 'Reglas',
                'zh_CN' => '规则',
                'pt_PT' => 'Regras'
            ],
            'sections' => [
                'Правила' => [
                    'title' => [
                        'en' => 'Rules',
                        'de' => 'Regeln',
                        'fr' => 'Règles',
                        'es' => 'Reglas',
                        'zh_CN' => '规则',
                        'pt_PT' => 'Regras'
                    ]
                ]
            ]
        ],
        'Контакты' => [
            'title' => [
                'en' => 'Contacts',
                'de' => 'Kontakte',
                'fr' => 'Contacts',
                'es' => 'Contactos',
                'zh_CN' => '联系方式',
                'pt_PT' => 'Contactos'
            ],
            'sections' => [
                'Контакт' => [
                    'title' => [
                        'en' => 'Contact',
                        'de' => 'Kontakt',
                        'fr' => 'Contact',
                        'es' => 'Contacto',
                        'zh_CN' => '联系',
                        'pt_PT' => 'Contacto'
                    ]
                ],
                'Ссылки на социальные сети' => [
                    'title' => [
                        'en' => 'Social Media Links',
                        'de' => 'Social-Media-Links',
                        'fr' => 'Liens vers les médias sociaux',
                        'es' => 'Enlaces a redes sociales',
                        'zh_CN' => '社交媒体链接',
                        'pt_PT' => 'Links de Redes Sociais'
                    ]
                ]
            ]
        ],
        'Оценка' => [
            'title' => [
                'en' => 'Evaluation',
                'de' => 'Bewertung',
                'fr' => 'Évaluation',
                'es' => 'Evaluación',
                'zh_CN' => '评估',
                'pt_PT' => 'Avaliação'
            ],
            'sections' => [
                'Критерии оценки' => [
                    'title' => [
                        'en' => 'Evaluation Criteria',
                        'de' => 'Bewertungskriterien',
                        'fr' => 'Critères d\'évaluation',
                        'es' => 'Criterios de evaluación',
                        'zh_CN' => '评估标准',
                        'pt_PT' => 'Critérios de Avaliação'
                    ]
                ]
            ]
        ],
        'Награды' => [
            'title' => [
                'en' => 'Prizes',
                'de' => 'Preise',
                'fr' => 'Prix',
                'es' => 'Premios',
                'zh_CN' => '奖品',
                'pt_PT' => 'Prémios'
            ],
            'sections' => [
                'Награды для участников' => [
                    'title' => [
                        'en' => 'Prizes for Participants',
                        'de' => 'Preise für Teilnehmer',
                        'fr' => 'Prix pour les participants',
                        'es' => 'Premios para participantes',
                        'zh_CN' => '参与者奖品',
                        'pt_PT' => 'Prémios para Participantes'
                    ]
                ]
            ]
        ]
    ];

    public static function defaultStructure(): array
    {
        return [
            'Обзор' => ['Описание', 'План проведения'],
            'Ресурсы' => ['Ресурсы'],
            'Правила' => ['Правила'],
            'Контакты' => ['Контакт', 'Ссылки на социальные сети'],
            'Оценка' => ['Критерии оценки'],
            'Награды' => ['Награды для участников'],
        ];
    }

    /**
     * @return BelongsTo
     */
    public function hackathon(): BelongsTo
    {
        return $this->belongsTo(Hackathon::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(TabSection::class)->orderBy('id');
    }
}
