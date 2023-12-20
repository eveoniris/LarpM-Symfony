-- current larp schema

create table if not exists age
(
    id             int auto_increment
        primary key,
    label          varchar(100)  not null,
    description    varchar(450)  null,
    bonus          int           null,
    enableCreation tinyint(1)    not null,
    discr          varchar(255)  not null,
    minimumValue   int default 0 not null
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create table if not exists appelation
(
    id            int auto_increment
        primary key,
    appelation_id int          null,
    label         varchar(45)  not null,
    description   longtext     null,
    titre         varchar(45)  null,
    discr         varchar(255) not null,
    constraint FK_68825BB0F9E65DDB
        foreign key (appelation_id) references appelation (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists fk_territoire_denomination_territoire_denomination1_idx
    on appelation (appelation_id);

create table if not exists attribute_type
(
    id    int auto_increment
        primary key,
    label varchar(45)  not null,
    discr varchar(255) not null
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create table if not exists classe
(
    id             int auto_increment
        primary key,
    label_masculin varchar(45)  null,
    label_feminin  varchar(45)  null,
    description    varchar(450) null,
    discr          varchar(255) not null,
    image_m        varchar(90)  null,
    image_f        varchar(90)  null,
    creation       tinyint(1)   null
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create table if not exists competence_family
(
    id          int auto_increment
        primary key,
    label       varchar(45)  not null,
    description varchar(450) null,
    discr       varchar(255) not null
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create table if not exists classe_competence_family_creation
(
    classe_id            int not null,
    competence_family_id int not null,
    primary key (classe_id, competence_family_id),
    constraint FK_4FC70A4B8F5EA509
        foreign key (classe_id) references classe (id),
    constraint FK_4FC70A4BF7EB2017
        foreign key (competence_family_id) references competence_family (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists IDX_4FC70A4B8F5EA509
    on classe_competence_family_creation (classe_id);

create index if not exists IDX_4FC70A4BF7EB2017
    on classe_competence_family_creation (competence_family_id);

create table if not exists classe_competence_family_favorite
(
    classe_id            int not null,
    competence_family_id int not null,
    primary key (classe_id, competence_family_id),
    constraint FK_70EC01E68F5EA509
        foreign key (classe_id) references classe (id),
    constraint FK_70EC01E6F7EB2017
        foreign key (competence_family_id) references competence_family (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists IDX_70EC01E68F5EA509
    on classe_competence_family_favorite (classe_id);

create index if not exists IDX_70EC01E6F7EB2017
    on classe_competence_family_favorite (competence_family_id);

create table if not exists classe_competence_family_normale
(
    classe_id            int not null,
    competence_family_id int not null,
    primary key (classe_id, competence_family_id),
    constraint FK_D65491848F5EA509
        foreign key (classe_id) references classe (id),
    constraint FK_D6549184F7EB2017
        foreign key (competence_family_id) references competence_family (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists IDX_D65491848F5EA509
    on classe_competence_family_normale (classe_id);

create index if not exists IDX_D6549184F7EB2017
    on classe_competence_family_normale (competence_family_id);

create table if not exists connaissance
(
    id          int(11) unsigned auto_increment
        primary key,
    label       varchar(45) collate utf8mb3_unicode_ci                    not null,
    description longtext                                                  null,
    contraintes longtext collate utf8mb3_unicode_ci                       null,
    documentUrl varchar(45) collate utf8mb3_unicode_ci                    null,
    niveau      int(2) unsigned                        default 1          not null,
    secret      tinyint(1)                             default 0          not null,
    discr       varchar(45) collate utf8mb3_unicode_ci default 'extended' not null
)
    engine = InnoDB
    charset = utf8mb3;

create table if not exists construction
(
    id          int auto_increment
        primary key,
    label       varchar(45)  not null,
    description longtext     null,
    defense     int          not null,
    discr       varchar(255) not null
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create table if not exists culture
(
    id                   int unsigned auto_increment
        primary key,
    label                varchar(45)  not null,
    description          longtext     not null,
    discr                varchar(255) not null,
    description_complete longtext     null
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create table if not exists domaine
(
    id          int auto_increment
        primary key,
    label       varchar(45)  not null,
    description longtext     null,
    discr       varchar(255) not null
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create table if not exists etat
(
    id    int auto_increment
        primary key,
    label varchar(45)  not null,
    discr varchar(255) not null
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create table if not exists etat_civil
(
    id                  int auto_increment
        primary key,
    nom                 varchar(45)  null,
    prenom              varchar(45)  null,
    prenom_usage        varchar(45)  null,
    telephone           varchar(45)  null,
    photo               varchar(45)  null,
    date_naissance      datetime     null,
    probleme_medicaux   longtext     null,
    personne_a_prevenir varchar(45)  null,
    tel_pap             varchar(45)  null,
    fedegn              varchar(45)  null,
    creation_date       datetime     not null,
    update_date         datetime     not null,
    discr               varchar(255) not null
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create table if not exists evenement
(
    id            int unsigned auto_increment
        primary key,
    text          varchar(450) not null,
    date          varchar(45)  not null,
    date_creation datetime     not null,
    date_update   datetime     not null,
    discr         varchar(255) not null
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create table if not exists formulaire
(
    id    int          not null
        primary key,
    title varchar(45)  null,
    discr varchar(255) not null
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create table if not exists genre
(
    id          int auto_increment
        primary key,
    label       varchar(100) not null,
    description varchar(450) null,
    discr       varchar(255) not null
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create table if not exists groupe
(
    id              int auto_increment
        primary key,
    scenariste_id   int unsigned null,
    responsable_id  int unsigned null,
    topic_id        int          not null,
    nom             varchar(100) null,
    description     longtext     null,
    numero          int          not null,
    code            varchar(10)  null,
    jeu_maritime    tinyint(1)   null,
    jeu_strategique tinyint(1)   null,
    classe_open     int          null,
    pj              tinyint(1)   null,
    discr           varchar(255) not null,
    materiel        longtext     null,
    `lock`          tinyint(1)   not null,
    territoire_id   int          null,
    richesse        int          null
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists fk_groupe_territoire1_idx
    on groupe (territoire_id);

create index if not exists fk_groupe_topic1_idx
    on groupe (topic_id);

create index if not exists fk_groupe_user2_idx
    on groupe (responsable_id);

create index if not exists fk_groupe_users1_idx
    on groupe (scenariste_id);

create table if not exists groupe_allie
(
    id                    int auto_increment
        primary key,
    groupe_id             int          not null,
    groupe_allie_id       int          not null,
    groupe_accepted       tinyint(1)   not null,
    groupe_allie_accepted tinyint(1)   not null,
    message               longtext     null,
    discr                 varchar(255) not null,
    message_allie         longtext     null,
    constraint FK_8E0758767A45358C
        foreign key (groupe_id) references groupe (id),
    constraint FK_8E075876DA3B93A3
        foreign key (groupe_allie_id) references groupe (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists fk_groupe_allie_groupe1_idx
    on groupe_allie (groupe_id);

create index if not exists fk_groupe_allie_groupe2_idx
    on groupe_allie (groupe_allie_id);

create table if not exists groupe_classe
(
    id        int auto_increment
        primary key,
    groupe_id int          not null,
    classe_id int          not null,
    discr     varchar(255) not null,
    constraint FK_E4B943AC7A45358C
        foreign key (groupe_id) references groupe (id),
    constraint FK_E4B943AC8F5EA509
        foreign key (classe_id) references classe (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists fk_groupe_classe_classe1_idx
    on groupe_classe (classe_id);

create index if not exists fk_groupe_classe_groupe1_idx
    on groupe_classe (groupe_id);

create table if not exists groupe_enemy
(
    id                 int auto_increment
        primary key,
    groupe_id          int          not null,
    groupe_enemy_id    int          not null,
    groupe_peace       tinyint(1)   not null,
    groupe_enemy_peace tinyint(1)   not null,
    message            longtext     null,
    discr              varchar(255) not null,
    message_enemy      longtext     null,
    constraint FK_AE3294F91AE935F
        foreign key (groupe_enemy_id) references groupe (id),
    constraint FK_AE3294F97A45358C
        foreign key (groupe_id) references groupe (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists fk_groupe_enemy_groupe1_idx
    on groupe_enemy (groupe_id);

create index if not exists fk_groupe_enemy_groupe2_idx
    on groupe_enemy (groupe_enemy_id);

create table if not exists groupe_langue
(
    id      int auto_increment
        primary key,
    label   varchar(100) not null,
    couleur varchar(45)  not null,
    discr   varchar(255) not null
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create table if not exists ingredient
(
    id          int auto_increment
        primary key,
    label       varchar(45)  not null,
    description longtext     null,
    niveau      int          not null,
    dose        varchar(45)  not null,
    discr       varchar(255) not null
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create table if not exists groupe_has_ingredient
(
    id            int auto_increment
        primary key,
    groupe_id     int          not null,
    ingredient_id int          not null,
    quantite      int          not null,
    discr         varchar(255) not null,
    constraint FK_EAACBE7A7A45358C
        foreign key (groupe_id) references groupe (id),
    constraint FK_EAACBE7A933FE08C
        foreign key (ingredient_id) references ingredient (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists fk_groupe_has_ingredient_groupe1_idx
    on groupe_has_ingredient (groupe_id);

create index if not exists fk_groupe_has_ingredient_ingredient1_idx
    on groupe_has_ingredient (ingredient_id);

create table if not exists item_bak
(
    id             int unsigned auto_increment
        primary key,
    quality_id     int           not null,
    statut_id      int           null,
    label          varchar(45)   null,
    description    longtext      null,
    numero         int           not null,
    identification int           not null,
    special        longtext      null,
    couleur        varchar(45)   not null,
    date_creation  datetime      not null,
    date_update    datetime      not null,
    discr          varchar(255)  not null,
    objet_id       int           not null,
    quantite       int default 1 not null
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists fk_item_objet1_idx
    on item_bak (objet_id);

create index if not exists fk_item_qualite1_idx
    on item_bak (quality_id);

create index if not exists fk_item_statut1_idx
    on item_bak (statut_id);

create table if not exists langue
(
    id               int auto_increment
        primary key,
    label            varchar(100)         not null,
    description      varchar(450)         null,
    discr            varchar(255)         not null,
    diffusion        int                  null,
    groupe_langue_id int                  not null,
    secret           tinyint(1) default 0 not null,
    documentUrl      varchar(45)          null
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists groupe_langue_id_idx
    on langue (groupe_langue_id);

create table if not exists level
(
    id          int auto_increment
        primary key,
    `index`     int          not null,
    label       varchar(45)  not null,
    cout        int          null,
    cout_favori int          null,
    cout_meconu int          null,
    discr       varchar(255) not null
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create table if not exists competence
(
    id                   int auto_increment
        primary key,
    competence_family_id int          not null,
    level_id             int          null,
    description          longtext     null,
    discr                varchar(255) not null,
    documentUrl          varchar(45)  null,
    materiel             longtext     null,
    constraint FK_94D4687F5FB14BA7
        foreign key (level_id) references level (id),
    constraint FK_94D4687FF7EB2017
        foreign key (competence_family_id) references competence_family (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists fk_competence_niveau_competence1_idx
    on competence (competence_family_id);

create index if not exists fk_competence_niveau_niveau1_idx
    on competence (level_id);

create table if not exists competence_attribute
(
    competence_id     int          not null,
    attribute_type_id int          not null,
    value             int          not null,
    discr             varchar(255) not null,
    primary key (competence_id, attribute_type_id),
    constraint FK_CECF998615761DAB
        foreign key (competence_id) references competence (id),
    constraint FK_CECF99864ED0D557
        foreign key (attribute_type_id) references attribute_type (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists fk_competence_has_attribute_type_attribute_type1_idx
    on competence_attribute (attribute_type_id);

create index if not exists fk_competence_has_attribute_type_competence1_idx
    on competence_attribute (competence_id);

create table if not exists lieu
(
    id          int auto_increment
        primary key,
    nom         varchar(45)  not null,
    description longtext     null,
    discr       varchar(255) not null
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create table if not exists lignees
(
    id          int(11) unsigned auto_increment
        primary key,
    nom         varchar(255)                    not null,
    description text                            null,
    discr       varchar(255) default 'extended' not null
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create table if not exists localisation
(
    id          int auto_increment
        primary key,
    label       varchar(45)  not null,
    `precision` varchar(450) null,
    discr       varchar(255) not null
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create table if not exists loi
(
    id          int unsigned auto_increment
        primary key,
    label       varchar(45)  null,
    documentUrl varchar(45)  null,
    description longtext     null,
    discr       varchar(255) not null
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create table if not exists monnaie
(
    id          int auto_increment
        primary key,
    label       varchar(45)  not null,
    description longtext     not null,
    discr       varchar(255) not null
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create table if not exists objectif
(
    id            int unsigned auto_increment
        primary key,
    text          varchar(450) not null,
    date_creation datetime     not null,
    date_update   datetime     not null,
    discr         varchar(255) not null
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create table if not exists personnage
(
    id            int auto_increment
        primary key,
    groupe_id     int          null,
    classe_id     int          not null,
    age_id        int          not null,
    genre_id      int          not null,
    nom           varchar(100) not null,
    surnom        varchar(100) null,
    intrigue      tinyint(1)   null,
    renomme       int          null,
    photo         varchar(100) null,
    xp            int          null,
    territoire_id int          null,
    discr         varchar(255) not null,
    materiel      longtext     null,
    vivant        tinyint(1)   not null,
    age_reel      int          null,
    trombineUrl   varchar(45)  null,
    user_id       int unsigned null,
    richesse      int          null,
    heroisme      int          null,
    sensible      tinyint(1)   null,
    constraint FK_6AEA486D4296D31F
        foreign key (genre_id) references genre (id),
    constraint FK_6AEA486D7A45358C
        foreign key (groupe_id) references groupe (id),
    constraint FK_6AEA486D8F5EA509
        foreign key (classe_id) references classe (id),
    constraint FK_6AEA486DCC80CD12
        foreign key (age_id) references age (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create table if not exists experience_gain
(
    id             int auto_increment
        primary key,
    personnage_id  int          not null,
    explanation    varchar(100) not null,
    operation_date datetime     not null,
    xp_gain        int          not null,
    discr          varchar(255) not null,
    constraint FK_8485E21D5E315342
        foreign key (personnage_id) references personnage (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists fk_experience_gain_personnage1_idx
    on experience_gain (personnage_id);

create table if not exists experience_usage
(
    id             int auto_increment
        primary key,
    competence_id  int          not null,
    personnage_id  int          not null,
    operation_date datetime     not null,
    xp_use         int          not null,
    discr          varchar(255) not null,
    constraint FK_B3B9226615761DAB
        foreign key (competence_id) references competence (id),
    constraint FK_B3B922665E315342
        foreign key (personnage_id) references personnage (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists fk_experience_usage_competence1_idx
    on experience_usage (competence_id);

create index if not exists fk_experience_usage_personnage1_idx
    on experience_usage (personnage_id);

create table if not exists heroisme_history
(
    id            int unsigned auto_increment
        primary key,
    personnage_id int          not null,
    date          date         not null,
    heroisme      int          not null,
    explication   longtext     not null,
    discr         varchar(255) not null,
    constraint FK_23D4BD695E315342
        foreign key (personnage_id) references personnage (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists fk_heroisme_history_personnage1_idx
    on heroisme_history (personnage_id);

create index if not exists fk_personnage_age1_idx
    on personnage (age_id);

create index if not exists fk_personnage_archetype1_idx
    on personnage (classe_id);

create index if not exists fk_personnage_genre1_idx
    on personnage (genre_id);

create index if not exists fk_personnage_groupe1_idx
    on personnage (groupe_id);

create index if not exists fk_personnage_territoire1_idx
    on personnage (territoire_id);

create index if not exists fk_personnage_user1_idx
    on personnage (user_id);

create table if not exists personnage_ingredient
(
    id            int unsigned auto_increment
        primary key,
    personnage_id int          not null,
    ingredient_id int          not null,
    nombre        int          null,
    discr         varchar(255) not null,
    constraint FK_F0FAA3655E315342
        foreign key (personnage_id) references personnage (id),
    constraint FK_F0FAA365933FE08C
        foreign key (ingredient_id) references ingredient (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists IDX_F0FAA3655E315342
    on personnage_ingredient (personnage_id);

create index if not exists fk_personnage_ingredient_ingredient1_idx
    on personnage_ingredient (ingredient_id);

create table if not exists personnage_langues
(
    id            int auto_increment
        primary key,
    personnage_id int          not null,
    langue_id     int          not null,
    source        varchar(45)  not null,
    discr         varchar(255) not null,
    constraint FK_3D820E582AADBACD
        foreign key (langue_id) references langue (id),
    constraint FK_3D820E585E315342
        foreign key (personnage_id) references personnage (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists fk_personnage_langues_langue1_idx
    on personnage_langues (langue_id);

create index if not exists fk_personnage_langues_personnage1_idx
    on personnage_langues (personnage_id);

create table if not exists personnage_secondaire
(
    id        int auto_increment
        primary key,
    classe_id int          not null,
    discr     varchar(255) not null,
    constraint FK_EACE838A8F5EA509
        foreign key (classe_id) references classe (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists fk_personnage_secondaire_classe1_idx
    on personnage_secondaire (classe_id);

create table if not exists personnage_secondaire_competence
(
    id                       int auto_increment
        primary key,
    personnage_secondaire_id int          not null,
    competence_id            int          not null,
    discr                    varchar(255) not null,
    constraint FK_DF6A66D15761DAB
        foreign key (competence_id) references competence (id),
    constraint FK_DF6A66DE6917FB3
        foreign key (personnage_secondaire_id) references personnage_secondaire (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists fk_personnage_secondaire_competences_competence1_idx
    on personnage_secondaire_competence (competence_id);

create index if not exists fk_personnage_secondaire_competences_personnage_secondaire_idx
    on personnage_secondaire_competence (personnage_secondaire_id);

create table if not exists personnage_secondaires_competences
(
    id                       int auto_increment
        primary key,
    personnage_secondaire_id int          not null,
    competence_id            int          not null,
    discr                    varchar(255) not null,
    constraint FK_A871317815761DAB
        foreign key (competence_id) references competence (id),
    constraint FK_A8713178E6917FB3
        foreign key (personnage_secondaire_id) references personnage_secondaire (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists fk_personnage_secondaires_competences_competence1_idx
    on personnage_secondaires_competences (competence_id);

create index if not exists fk_personnage_secondaires_competences_personnage_secondaire_idx
    on personnage_secondaires_competences (personnage_secondaire_id);

create table if not exists personnage_secondaires_skills
(
    id                       int auto_increment
        primary key,
    personnage_secondaire_id int          not null,
    competence_id            int          not null,
    discr                    varchar(255) not null,
    constraint FK_C410AE5015761DAB
        foreign key (competence_id) references competence (id),
    constraint FK_C410AE50E6917FB3
        foreign key (personnage_secondaire_id) references personnage_secondaire (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists fk_personnage_secondaire_skills_competence1_idx
    on personnage_secondaires_skills (competence_id);

create index if not exists fk_personnage_secondaire_skills_personnage_secondaire_idx
    on personnage_secondaires_skills (personnage_secondaire_id);

create table if not exists personnage_trigger
(
    id            int auto_increment
        primary key,
    personnage_id int          not null,
    tag           varchar(45)  not null,
    done          tinyint(1)   not null,
    discr         varchar(255) not null,
    constraint FK_3674375C5E315342
        foreign key (personnage_id) references personnage (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists fk_trigger_personnage1_idx
    on personnage_trigger (personnage_id);

create table if not exists personnages_chronologie
(
    id            int(11) unsigned auto_increment
        primary key,
    personnage_id int                                                        not null,
    evenement     varchar(255) collate utf8mb3_unicode_ci                    not null,
    annee         int(5)                                                     not null,
    discr         varchar(255) collate utf8mb3_unicode_ci default 'extended' not null,
    constraint FK_6ECC33456843
        foreign key (personnage_id) references personnage (id)
)
    engine = InnoDB
    charset = latin1;

create table if not exists personnages_competences
(
    competence_id int not null,
    personnage_id int not null,
    primary key (competence_id, personnage_id),
    constraint FK_8AED412315761DAB
        foreign key (competence_id) references competence (id),
    constraint FK_8AED41235E315342
        foreign key (personnage_id) references personnage (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists IDX_8AED412315761DAB
    on personnages_competences (competence_id);

create index if not exists IDX_8AED41235E315342
    on personnages_competences (personnage_id);

create table if not exists personnages_connaissances
(
    id              int(11) unsigned auto_increment
        primary key,
    personnage_id   int                                                        not null,
    connaissance_id int(11) unsigned                                           null,
    discr           varchar(255) collate utf8mb3_unicode_ci default 'extended' not null,
    constraint FK_CONNAISSANCES
        foreign key (connaissance_id) references connaissance (id),
    constraint FK_PERSONNAGES_CONNAISSANCES
        foreign key (personnage_id) references personnage (id)
)
    engine = InnoDB
    charset = latin1;

create table if not exists personnages_domaines
(
    personnage_id int not null,
    domaine_id    int not null,
    primary key (personnage_id, domaine_id),
    constraint FK_C31CED644272FC9F
        foreign key (domaine_id) references domaine (id),
    constraint FK_C31CED645E315342
        foreign key (personnage_id) references personnage (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists IDX_C31CED644272FC9F
    on personnages_domaines (domaine_id);

create index if not exists IDX_C31CED645E315342
    on personnages_domaines (personnage_id);

create table if not exists personnages_lignee
(
    id            int(11) unsigned auto_increment
        primary key,
    personnage_id int                                                        not null,
    parent1_id    int                                                        null,
    parent2_id    int                                                        null,
    lignee_id     int(11) unsigned                                           null,
    discr         varchar(255) collate utf8mb3_unicode_ci default 'extended' not null,
    constraint FK_6ECC33456844
        foreign key (parent1_id) references personnage (id),
    constraint FK_6ECC33456845
        foreign key (parent2_id) references personnage (id),
    constraint FK_6ECC33456846
        foreign key (lignee_id) references lignees (id),
    constraint FK_6ECC33456847
        foreign key (personnage_id) references personnage (id)
)
    engine = InnoDB
    charset = latin1;

create table if not exists photo
(
    id            int auto_increment
        primary key,
    name          varchar(45)  not null,
    extension     varchar(45)  not null,
    real_name     varchar(45)  not null,
    data          longblob     null,
    creation_date datetime     not null,
    discr         varchar(255) not null,
    filename      varchar(100) not null
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create table if not exists potion
(
    id          int auto_increment
        primary key,
    label       varchar(45)  not null,
    description longtext     null,
    documentUrl varchar(45)  null,
    discr       varchar(255) not null,
    niveau      int          not null,
    numero      varchar(45)  not null,
    secret      tinyint(1)   null
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create table if not exists personnages_potions
(
    personnage_id int not null,
    potion_id     int not null,
    primary key (personnage_id, potion_id),
    constraint FK_D485198A5E315342
        foreign key (personnage_id) references personnage (id),
    constraint FK_D485198A7126B348
        foreign key (potion_id) references potion (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists IDX_D485198A5E315342
    on personnages_potions (personnage_id);

create index if not exists IDX_D485198A7126B348
    on personnages_potions (potion_id);

create table if not exists proprietaire
(
    id      int auto_increment
        primary key,
    nom     varchar(100) null,
    adresse varchar(450) null,
    mail    varchar(100) null,
    tel     varchar(100) null,
    discr   varchar(255) not null
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create table if not exists pugilat_history
(
    id            int unsigned auto_increment
        primary key,
    personnage_id int          not null,
    date          date         not null,
    pugilat       int          not null,
    explication   longtext     not null,
    discr         varchar(255) not null,
    constraint FK_864C39CB5E315342
        foreign key (personnage_id) references personnage (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists fk_pugilat_history_personnage1_idx
    on pugilat_history (personnage_id);

create table if not exists quality
(
    id     int auto_increment
        primary key,
    label  varchar(45)  not null,
    numero int          null,
    discr  varchar(255) not null
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create table if not exists quality_valeur
(
    id         int unsigned auto_increment
        primary key,
    quality_id int          not null,
    monnaie_id int          not null,
    nombre     int          not null,
    discr      varchar(255) not null,
    constraint FK_A480028F98D3FE22
        foreign key (monnaie_id) references monnaie (id),
    constraint FK_A480028FBCFC6D57
        foreign key (quality_id) references quality (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists fk_quality_valeur_monnaie1_idx
    on quality_valeur (monnaie_id);

create index if not exists fk_quality_valeur_qualite1_idx
    on quality_valeur (quality_id);

create table if not exists rangement
(
    id              int auto_increment
        primary key,
    localisation_id int          null,
    label           varchar(45)  null,
    `precision`     varchar(450) null,
    discr           varchar(255) not null,
    constraint FK_90F17AA6C68BE09C
        foreign key (localisation_id) references localisation (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists fk_rangement_localisation1_idx
    on rangement (localisation_id);

create table if not exists rarete
(
    id    int auto_increment
        primary key,
    label varchar(45)  not null,
    value int          not null,
    discr varchar(255) not null
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create table if not exists religion_level
(
    id          int auto_increment
        primary key,
    label       varchar(45)  not null,
    `index`     int          not null,
    description longtext     null,
    discr       varchar(255) not null
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create table if not exists renomme_history
(
    id            int unsigned auto_increment
        primary key,
    personnage_id int          not null,
    renomme       int          not null,
    explication   longtext     not null,
    date          datetime     not null,
    discr         varchar(255) not null,
    constraint FK_40D972425E315342
        foreign key (personnage_id) references personnage (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists fk_renomme_history_personnage1_idx
    on renomme_history (personnage_id);

create table if not exists ressource
(
    id        int auto_increment
        primary key,
    rarete_id int          not null,
    label     varchar(100) not null,
    discr     varchar(255) not null,
    constraint FK_939F45449E795D2F
        foreign key (rarete_id) references rarete (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create table if not exists groupe_has_ressource
(
    id           int auto_increment
        primary key,
    groupe_id    int          not null,
    ressource_id int          not null,
    quantite     int          not null,
    discr        varchar(255) not null,
    constraint FK_2E4F82907A45358C
        foreign key (groupe_id) references groupe (id),
    constraint FK_2E4F8290FC6CD52A
        foreign key (ressource_id) references ressource (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists fk_groupe_has_ressource_groupe1_idx
    on groupe_has_ressource (groupe_id);

create index if not exists fk_groupe_has_ressource_ressource1_idx
    on groupe_has_ressource (ressource_id);

create table if not exists personnage_ressource
(
    id            int unsigned auto_increment
        primary key,
    personnage_id int          not null,
    ressource_id  int          not null,
    nombre        int          null,
    discr         varchar(255) not null,
    constraint FK_A286E0845E315342
        foreign key (personnage_id) references personnage (id),
    constraint FK_A286E084FC6CD52A
        foreign key (ressource_id) references ressource (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists IDX_A286E0845E315342
    on personnage_ressource (personnage_id);

create index if not exists fk_personnage_ressource_ressource1_idx
    on personnage_ressource (ressource_id);

create index if not exists fk_ressource_rarete1_idx
    on ressource (rarete_id);

create table if not exists restauration
(
    id          int auto_increment
        primary key,
    label       varchar(45)  not null,
    description longtext     null,
    discr       varchar(255) not null
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create table if not exists rule
(
    id          int auto_increment
        primary key,
    label       varchar(45)  not null,
    url         varchar(45)  not null,
    description longtext     null,
    discr       varchar(255) not null
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create table if not exists secondary_group_type
(
    id          int auto_increment
        primary key,
    label       varchar(45)  not null,
    description longtext     null,
    discr       varchar(255) not null
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create table if not exists sort
(
    id          int auto_increment
        primary key,
    domaine_id  int                  not null,
    label       varchar(45)          not null,
    description longtext             null,
    documentUrl varchar(45)          null,
    niveau      int                  not null,
    discr       varchar(255)         not null,
    secret      tinyint(1) default 0 not null,
    constraint FK_5124F2224272FC9F
        foreign key (domaine_id) references domaine (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create table if not exists personnages_sorts
(
    personnage_id int not null,
    sort_id       int not null,
    primary key (personnage_id, sort_id),
    constraint FK_8ABC9FD747013001
        foreign key (sort_id) references sort (id),
    constraint FK_8ABC9FD75E315342
        foreign key (personnage_id) references personnage (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists IDX_8ABC9FD747013001
    on personnages_sorts (sort_id);

create index if not exists IDX_8ABC9FD75E315342
    on personnages_sorts (personnage_id);

create index if not exists fk_sort_domaine1_idx
    on sort (domaine_id);

create table if not exists sorts
(
    id          int auto_increment
        primary key,
    domaine_id  int          not null,
    label       varchar(45)  not null,
    description longtext     null,
    documentUrl varchar(45)  null,
    niveau      int          not null,
    discr       varchar(255) not null,
    constraint FK_CE3FAA1D4272FC9F
        foreign key (domaine_id) references domaine (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists fk_sorts_domaine1_idx
    on sorts (domaine_id);

create table if not exists sphere
(
    id    int auto_increment
        primary key,
    label varchar(45)  null,
    discr varchar(255) not null
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create table if not exists priere
(
    id          int auto_increment
        primary key,
    label       varchar(45)  not null,
    description longtext     null,
    annonce     longtext     null,
    documentUrl varchar(45)  null,
    niveau      int          not null,
    discr       varchar(255) not null,
    sphere_id   int          not null,
    constraint FK_1111202C75FD4EF9
        foreign key (sphere_id) references sphere (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create table if not exists personnages_prieres
(
    priere_id     int not null,
    personnage_id int not null,
    primary key (priere_id, personnage_id),
    constraint FK_4E610DAC5E315342
        foreign key (personnage_id) references personnage (id),
    constraint FK_4E610DACA8227EF5
        foreign key (priere_id) references priere (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists IDX_4E610DAC5E315342
    on personnages_prieres (personnage_id);

create index if not exists IDX_4E610DACA8227EF5
    on personnages_prieres (priere_id);

create index if not exists fk_priere_sphere1_idx
    on priere (sphere_id);

create table if not exists statut
(
    id          int auto_increment
        primary key,
    label       varchar(45)  null,
    description longtext     null,
    discr       varchar(255) not null
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create table if not exists tag
(
    id    int auto_increment
        primary key,
    nom   varchar(100) null,
    discr varchar(255) not null
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create table if not exists technologie
(
    id                   int unsigned auto_increment
        primary key,
    label                varchar(45)          not null,
    description          longtext             not null,
    discr                varchar(255)         not null,
    secret               tinyint(1) default 0 not null,
    documentUrl          varchar(45)          null,
    competence_family_id int                  null,
    constraint FK_technologie_competence_family
        foreign key (competence_family_id) references competence_family (id)
            on delete set null
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create table if not exists personnage_has_technologie
(
    personnage_id  int          not null,
    technologie_id int unsigned not null,
    primary key (personnage_id, technologie_id),
    constraint FK_65F62F93261A27D2
        foreign key (technologie_id) references technologie (id),
    constraint FK_65F62F935E315342
        foreign key (personnage_id) references personnage (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists IDX_65F62F93261A27D2
    on personnage_has_technologie (technologie_id);

create index if not exists IDX_65F62F935E315342
    on personnage_has_technologie (personnage_id);

create table if not exists technologies_ressources
(
    id             int unsigned auto_increment
        primary key,
    technologie_id int unsigned not null,
    ressource_id   int          not null,
    quantite       int          not null,
    discr          varchar(255) not null,
    constraint FK_B15E3D68261A27D2
        foreign key (technologie_id) references technologie (id)
            on delete cascade,
    constraint FK_B15E3D68FC6CD52A
        foreign key (ressource_id) references ressource (id)
)
    engine = InnoDB
    charset = latin1;

create index if not exists fk_technologies_ressources_idx
    on technologies_ressources (technologie_id);

create table if not exists territoire_guerre
(
    id            int auto_increment
        primary key,
    puissance     int          null,
    puissance_max int          null,
    protection    int          null,
    discr         varchar(255) not null
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create table if not exists territoire_quete
(
    territoire_id       int not null,
    territoire_cible_id int not null,
    primary key (territoire_id, territoire_cible_id)
)
    engine = MyISAM
    charset = utf8mb3;

create index if not exists IDX_63718DCB011823
    on territoire_quete (territoire_cible_id);

create index if not exists IDX_63718DCD0F97A8
    on territoire_quete (territoire_id);

create table if not exists titre
(
    id      int auto_increment
        primary key,
    label   varchar(45)  not null,
    renomme int          not null,
    discr   varchar(255) not null
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create table if not exists token
(
    id          int auto_increment
        primary key,
    label       varchar(45)  not null,
    description longtext     null,
    discr       varchar(255) not null,
    tag         varchar(45)  not null
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create table if not exists personnage_has_token
(
    id            int unsigned auto_increment
        primary key,
    personnage_id int          not null,
    token_id      int          not null,
    discr         varchar(255) not null,
    constraint FK_95A7144541DEE7B9
        foreign key (token_id) references token (id),
    constraint FK_95A714455E315342
        foreign key (personnage_id) references personnage (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists IDX_95A714455E315342
    on personnage_has_token (personnage_id);

create index if not exists fk_personnage_has_token_token1_idx
    on personnage_has_token (token_id);

create table if not exists `trigger`
(
    id            int auto_increment
        primary key,
    personnage_id int          not null,
    tag           varchar(45)  not null,
    done          tinyint(1)   not null,
    discr         varchar(255) not null,
    constraint FK_1A6B0F5D5E315342
        foreign key (personnage_id) references personnage (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists fk_trigger_idx
    on `trigger` (personnage_id);

create table if not exists user
(
    id                         int unsigned auto_increment
        primary key,
    etat_civil_id              int          null,
    email                      varchar(100) not null,
    password                   varchar(255) null,
    salt                       varchar(255) not null,
    rights                     varchar(255) not null,
    creation_date              datetime     not null,
    username                   varchar(100) not null,
    isEnabled                  tinyint(1)   not null,
    confirmationToken          varchar(100) null,
    timePasswordResetRequested int unsigned null,
    discr                      varchar(255) not null,
    trombineUrl                varchar(45)  null,
    lastConnectionDate         datetime     null,
    personnage_id              int          null,
    coeur                      int          null,
    personnage_secondaire_id   int          null,
    constraint email_UNIQUE
        unique (email),
    constraint id_UNIQUE
        unique (id),
    constraint username_UNIQUE
        unique (username),
    constraint FK_8D93D649191476EE
        foreign key (etat_civil_id) references etat_civil (id),
    constraint FK_8D93D6495E315342
        foreign key (personnage_id) references personnage (id),
    constraint FK_8D93D649E6917FB3
        foreign key (personnage_secondaire_id) references personnage_secondaire (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create table if not exists document
(
    id            int auto_increment
        primary key,
    user_id       int unsigned not null,
    code          varchar(45)  not null,
    titre         varchar(45)  not null,
    documentUrl   varchar(45)  not null,
    cryptage      tinyint(1)   not null,
    statut        varchar(45)  null,
    auteur        varchar(45)  null,
    creation_date datetime     not null,
    update_date   datetime     not null,
    discr         varchar(255) not null,
    description   longtext     null,
    impression    tinyint(1)   not null,
    constraint FK_D8698A76A76ED395
        foreign key (user_id) references user (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists fk_document_user1_idx
    on document (user_id);

create table if not exists document_has_langue
(
    document_id int not null,
    langue_id   int not null,
    primary key (document_id, langue_id),
    constraint FK_1EB6C9432AADBACD
        foreign key (langue_id) references langue (id),
    constraint FK_1EB6C943C33F7837
        foreign key (document_id) references document (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists IDX_1EB6C9432AADBACD
    on document_has_langue (langue_id);

create index if not exists IDX_1EB6C943C33F7837
    on document_has_langue (document_id);

alter table groupe
    add constraint FK_4B98C211674CEC6
        foreign key (scenariste_id) references user (id);

alter table groupe
    add constraint FK_4B98C2153C59D72
        foreign key (responsable_id) references user (id);

create table if not exists groupe_has_document
(
    groupe_id   int not null,
    Document_id int not null,
    primary key (groupe_id, Document_id),
    constraint FK_B55C428645A3F7E0
        foreign key (Document_id) references document (id),
    constraint FK_B55C42867A45358C
        foreign key (groupe_id) references groupe (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists IDX_B55C42867A45358C
    on groupe_has_document (groupe_id);

create index if not exists IDX_B55C4286C33F7837
    on groupe_has_document (Document_id);

create table if not exists intrigue
(
    id            int unsigned auto_increment
        primary key,
    user_id       int unsigned not null,
    titre         varchar(45)  not null,
    text          longtext     not null,
    resolution    longtext     null,
    date_creation datetime     not null,
    date_update   datetime     not null,
    discr         varchar(255) not null,
    description   longtext     not null,
    state         varchar(45)  null,
    constraint FK_688D271A76ED395
        foreign key (user_id) references user (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists fk_intrigue_user1_idx
    on intrigue (user_id);

create table if not exists intrigue_has_document
(
    id          int unsigned auto_increment
        primary key,
    intrigue_id int unsigned not null,
    document_id int          not null,
    discr       varchar(255) not null,
    constraint FK_928B0219631F6DBE
        foreign key (intrigue_id) references intrigue (id),
    constraint FK_928B02196AB21CC3
        foreign key (document_id) references document (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists fk_intrigue_has_document_document1_idx
    on intrigue_has_document (document_id);

create index if not exists fk_intrigue_has_document_intrigue1_idx
    on intrigue_has_document (intrigue_id);

create table if not exists intrigue_has_evenement
(
    id           int unsigned auto_increment
        primary key,
    intrigue_id  int unsigned not null,
    evenement_id int unsigned not null,
    discr        varchar(255) not null,
    constraint FK_B5719C3C631F6BDE
        foreign key (intrigue_id) references intrigue (id),
    constraint FK_B5719C3CFD02F13
        foreign key (evenement_id) references evenement (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists fk_intrigue_has_evenement_evenement1_idx
    on intrigue_has_evenement (evenement_id);

create index if not exists fk_intrigue_has_evenement_intrigue1_idx
    on intrigue_has_evenement (intrigue_id);

create table if not exists intrigue_has_groupe
(
    id          int unsigned auto_increment
        primary key,
    intrigue_id int unsigned not null,
    groupe_id   int          not null,
    discr       varchar(255) not null,
    constraint FK_347A1255631F6BDE
        foreign key (intrigue_id) references intrigue (id),
    constraint FK_347A12557A45358C
        foreign key (groupe_id) references groupe (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists fk_intrigue_has_groupe_groupe1_idx
    on intrigue_has_groupe (groupe_id);

create index if not exists fk_intrigue_has_groupe_intrigue1_idx
    on intrigue_has_groupe (intrigue_id);

create table if not exists intrigue_has_lieu
(
    id          int unsigned auto_increment
        primary key,
    intrigue_id int unsigned not null,
    lieu_id     int          not null,
    discr       varchar(255) not null,
    constraint FK_928B0219631F6BDE
        foreign key (intrigue_id) references intrigue (id),
    constraint FK_928B02196AB213CC
        foreign key (lieu_id) references lieu (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists fk_intrigue_has_lieu_intrigue1_idx
    on intrigue_has_lieu (intrigue_id);

create index if not exists fk_intrigue_has_lieu_lieu1_idx
    on intrigue_has_lieu (lieu_id);

create table if not exists intrigue_has_modification
(
    id          int unsigned auto_increment
        primary key,
    intrigue_id int unsigned not null,
    user_id     int unsigned not null,
    date        datetime     not null,
    discr       varchar(255) not null,
    constraint FK_5172570C631F6BDE
        foreign key (intrigue_id) references intrigue (id),
    constraint FK_5172570CA76ED395
        foreign key (user_id) references user (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists fk_intrigue_has_modification_intrigue1_idx
    on intrigue_has_modification (intrigue_id);

create index if not exists fk_intrigue_has_modification_user1_idx
    on intrigue_has_modification (user_id);

create table if not exists intrigue_has_objectif
(
    id          int unsigned auto_increment
        primary key,
    intrigue_id int unsigned not null,
    objectif_id int unsigned not null,
    discr       varchar(255) not null,
    constraint FK_BE1C5A23157D1AD4
        foreign key (objectif_id) references objectif (id),
    constraint FK_BE1C5A23631F6BDE
        foreign key (intrigue_id) references intrigue (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists fk_intrigue_has_objectif_intrigue1_idx
    on intrigue_has_objectif (intrigue_id);

create index if not exists fk_intrigue_has_objectif_objectif1_idx
    on intrigue_has_objectif (objectif_id);

create table if not exists lieu_has_document
(
    document_id int not null,
    lieu_id     int not null,
    primary key (lieu_id, document_id),
    constraint FK_487D3F926AB213CC
        foreign key (lieu_id) references lieu (id),
    constraint FK_487D3F92C33F7837
        foreign key (document_id) references document (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists IDX_487D3F926AB213CC
    on lieu_has_document (lieu_id);

create index if not exists IDX_487D3F92C33F7837
    on lieu_has_document (document_id);

create table if not exists message
(
    id            int auto_increment
        primary key,
    auteur        int unsigned not null,
    destinataire  int unsigned not null,
    title         varchar(45)  null,
    text          longtext     null,
    creation_date datetime     null,
    update_date   datetime     null,
    lu            tinyint(1)   null,
    discr         varchar(255) not null,
    constraint FK_B6BD307F55AB140
        foreign key (auteur) references user (id),
    constraint FK_B6BD307FFEA9FF92
        foreign key (destinataire) references user (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists fk_message_user1_idx
    on message (auteur);

create index if not exists fk_message_user2_idx
    on message (destinataire);

create table if not exists notification
(
    id      int unsigned auto_increment
        primary key,
    user_id int unsigned not null,
    text    longtext     not null,
    date    datetime     null,
    url     varchar(45)  null,
    discr   varchar(255) not null,
    constraint FK_BF5476CAA76ED395
        foreign key (user_id) references user (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists fk_notification_user1_idx
    on notification (user_id);

create table if not exists objet
(
    id              int auto_increment
        primary key,
    etat_id         int          null,
    proprietaire_id int          null,
    responsable_id  int unsigned null,
    photo_id        int          null,
    rangement_id    int          not null,
    numero          varchar(45)  not null,
    nom             varchar(100) not null,
    description     varchar(450) null,
    nombre          int          null,
    cout            double       null,
    budget          double       null,
    investissement  tinyint(1)   null,
    creation_date   datetime     null,
    discr           varchar(255) not null,
    constraint FK_46CD4C3853C59D72
        foreign key (responsable_id) references user (id),
    constraint FK_46CD4C3876C50E4A
        foreign key (proprietaire_id) references proprietaire (id),
    constraint FK_46CD4C387E9E4C8C
        foreign key (photo_id) references photo (id)
            on delete cascade,
    constraint FK_46CD4C38A4DEB784
        foreign key (rangement_id) references rangement (id),
    constraint FK_46CD4C38D5E86FF
        foreign key (etat_id) references etat (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create table if not exists item
(
    id             int unsigned auto_increment
        primary key,
    quality_id     int          not null,
    statut_id      int          null,
    label          varchar(45)  null,
    description    longtext     null,
    numero         int          not null,
    identification varchar(2)   not null,
    special        longtext     null,
    couleur        varchar(45)  not null,
    date_creation  datetime     not null,
    date_update    datetime     not null,
    discr          varchar(255) not null,
    objet_id       int          not null,
    quantite       int          not null,
    constraint FK_1F1B251EBCFC6D57
        foreign key (quality_id) references quality (id),
    constraint FK_1F1B251EF520CF5A
        foreign key (objet_id) references objet (id),
    constraint FK_1F1B251EF6203804
        foreign key (statut_id) references statut (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create table if not exists groupe_has_item
(
    groupe_id int          not null,
    item_id   int unsigned not null,
    primary key (groupe_id, item_id),
    constraint FK_D3E5F531126F525E
        foreign key (item_id) references item (id),
    constraint FK_D3E5F5317A45358C
        foreign key (groupe_id) references groupe (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists IDX_D3E5F531126F525E
    on groupe_has_item (item_id);

create index if not exists IDX_D3E5F5317A45358C
    on groupe_has_item (groupe_id);

create index if not exists fk_item_objet1_idx
    on item (objet_id);

create index if not exists fk_item_qualite1_idx
    on item (quality_id);

create index if not exists fk_item_statut1_idx
    on item (statut_id);

create index if not exists fk_objet_etat1_idx
    on objet (etat_id);

create index if not exists fk_objet_photo1_idx
    on objet (photo_id);

create index if not exists fk_objet_possesseur1_idx
    on objet (proprietaire_id);

create index if not exists fk_objet_rangement1_idx
    on objet (rangement_id);

create index if not exists fk_objet_users1_idx
    on objet (responsable_id);

create table if not exists objet_carac
(
    id       int auto_increment
        primary key,
    objet_id int          not null,
    taille   varchar(45)  null,
    poid     varchar(45)  null,
    couleur  varchar(45)  null,
    discr    varchar(255) not null,
    constraint FK_6B20A761F520CF5A
        foreign key (objet_id) references objet (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists fk_objet_carac_objet1_idx
    on objet_carac (objet_id);

create table if not exists objet_tag
(
    objet_id int not null,
    tag_id   int not null,
    primary key (objet_id, tag_id),
    constraint FK_E3164735BAD26311
        foreign key (tag_id) references tag (id),
    constraint FK_E3164735F520CF5A
        foreign key (objet_id) references objet (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists IDX_E3164735BAD26311
    on objet_tag (tag_id);

create index if not exists IDX_E3164735F520CF5A
    on objet_tag (objet_id);

alter table personnage
    add constraint FK_6AEA486DA76ED395
        foreign key (user_id) references user (id);

create table if not exists personnage_has_document
(
    personnage_id int not null,
    Document_id   int not null,
    primary key (personnage_id, Document_id),
    constraint FK_EFBB065F45A3F7E0
        foreign key (Document_id) references document (id),
    constraint FK_EFBB065F5E315342
        foreign key (personnage_id) references personnage (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists IDX_EFBB065F45A3F7E0
    on personnage_has_document (Document_id);

create index if not exists IDX_EFBB065F5E315342
    on personnage_has_document (personnage_id);

create table if not exists personnage_has_item
(
    personnage_id int          not null,
    item_id       int unsigned not null,
    primary key (personnage_id, item_id),
    constraint FK_356CD1EF126F525E
        foreign key (item_id) references item (id),
    constraint FK_356CD1EF5E315342
        foreign key (personnage_id) references personnage (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists IDX_356CD1EF126F525E
    on personnage_has_item (item_id);

create index if not exists IDX_356CD1EF5E315342
    on personnage_has_item (personnage_id);

create table if not exists question
(
    id      int unsigned auto_increment
        primary key,
    user_id int unsigned not null,
    text    longtext     not null,
    date    datetime     not null,
    discr   varchar(255) not null,
    choix   longtext     not null,
    label   varchar(45)  not null,
    constraint FK_B6F7494EA76ED395
        foreign key (user_id) references user (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create table if not exists personnage_has_question
(
    id            int unsigned auto_increment
        primary key,
    personnage_id int          not null,
    question_id   int unsigned not null,
    reponse       tinyint(1)   not null,
    discr         varchar(255) not null,
    constraint FK_8125C5671E27F6BF
        foreign key (question_id) references question (id),
    constraint FK_8125C5675E315342
        foreign key (personnage_id) references personnage (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists fk_personnage_has_question_personnage1_idx
    on personnage_has_question (personnage_id);

create index if not exists fk_personnage_has_question_question1_idx
    on personnage_has_question (question_id);

create index if not exists fk_question_user1_idx
    on question (user_id);

create table if not exists relecture
(
    id          int unsigned auto_increment
        primary key,
    user_id     int unsigned not null,
    intrigue_id int unsigned not null,
    date        datetime     not null,
    statut      varchar(45)  not null,
    discr       varchar(255) not null,
    remarque    longtext     null,
    constraint FK_FC5CF714631F6BDE
        foreign key (intrigue_id) references intrigue (id),
    constraint FK_FC5CF714A76ED395
        foreign key (user_id) references user (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists fk_relecture_intrigue1_idx
    on relecture (intrigue_id);

create index if not exists fk_relecture_user1_idx
    on relecture (user_id);

create table if not exists restriction
(
    id            int auto_increment
        primary key,
    label         varchar(90)  not null,
    discr         varchar(255) not null,
    auteur_id     int unsigned not null,
    creation_date datetime     null,
    update_date   datetime     null,
    constraint FK_7A999BCE60BB6FE6
        foreign key (auteur_id) references user (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists fk_restriction_user1_idx
    on restriction (auteur_id);

create table if not exists topic
(
    id            int auto_increment
        primary key,
    topic_id      int          null,
    user_id       int unsigned null,
    title         varchar(450) not null,
    description   longtext     null,
    creation_date datetime     null,
    update_date   datetime     null,
    `right`       varchar(45)  null,
    object_id     int          null,
    `key`         varchar(45)  null,
    discr         varchar(255) not null,
    constraint FK_9D40DE1B1F55203D
        foreign key (topic_id) references topic (id),
    constraint FK_9D40DE1BA76ED395
        foreign key (user_id) references user (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create table if not exists gn
(
    id                       int auto_increment
        primary key,
    topic_id                 int             not null,
    label                    varchar(45)     not null,
    xp_creation              int             null,
    description              longtext        null,
    date_debut               datetime        null,
    date_fin                 datetime        null,
    date_installation_joueur datetime        null,
    date_fin_orga            datetime        null,
    adresse                  varchar(45)     null,
    actif                    tinyint(1)      not null,
    discr                    varchar(255)    not null,
    billetterie              longtext        null,
    conditions_inscription   longtext        null,
    date_jeu                 int(5) unsigned null,
    constraint FK_C16FA3C01F55203D
        foreign key (topic_id) references topic (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create table if not exists annonce
(
    id            int auto_increment
        primary key,
    title         varchar(45)  not null,
    text          longtext     not null,
    creation_date datetime     not null,
    update_date   datetime     not null,
    archive       tinyint(1)   not null,
    discr         varchar(255) not null,
    gn_id         int          null,
    constraint FK_F65593E5AFC9C052
        foreign key (gn_id) references gn (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists fk_annonce_gn1_idx
    on annonce (gn_id);

create table if not exists background
(
    id            int auto_increment
        primary key,
    groupe_id     int          not null,
    user_id       int unsigned not null,
    text          longtext     null,
    visibility    varchar(45)  null,
    creation_date datetime     null,
    update_date   datetime     null,
    discr         varchar(255) not null,
    gn_id         int          null,
    titre         varchar(45)  not null,
    constraint FK_BC68B4507A45358C
        foreign key (groupe_id) references groupe (id),
    constraint FK_BC68B450A76ED395
        foreign key (user_id) references user (id),
    constraint FK_BC68B450AFC9C052
        foreign key (gn_id) references gn (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists fk_background_gn1_idx
    on background (gn_id);

create index if not exists fk_background_groupe1_idx
    on background (groupe_id);

create index if not exists fk_background_user1_idx
    on background (user_id);

create table if not exists billet
(
    id            int auto_increment
        primary key,
    createur_id   int unsigned not null,
    label         varchar(45)  not null,
    description   longtext     null,
    creation_date datetime     not null,
    update_date   datetime     not null,
    discr         varchar(255) not null,
    gn_id         int          not null,
    fedegn        tinyint(1)   not null,
    constraint FK_1F034AF673A201E5
        foreign key (createur_id) references user (id),
    constraint FK_1F034AF6AFC9C052
        foreign key (gn_id) references gn (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists fk_billet_gn1_idx
    on billet (gn_id);

create index if not exists fk_billet_user1_idx
    on billet (createur_id);

create table if not exists debriefing
(
    id            int auto_increment
        primary key,
    groupe_id     int          not null,
    user_id       int unsigned not null,
    gn_id         int          null,
    titre         varchar(45)  not null,
    text          longtext     null,
    visibility    varchar(45)  null,
    creation_date datetime     null,
    update_date   datetime     null,
    discr         varchar(255) not null,
    documentUrl   varchar(45)  null,
    player_id     int unsigned null,
    constraint FK_CB81225E7A45358C
        foreign key (groupe_id) references groupe (id),
    constraint FK_CB81225EA76ED395
        foreign key (user_id) references user (id),
    constraint FK_CB81225EAFC9C052
        foreign key (gn_id) references gn (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists fk_debriefing_gn1_idx
    on debriefing (gn_id);

create index if not exists fk_debriefing_groupe1_idx
    on debriefing (groupe_id);

create index if not exists fk_debriefing_player1_idx
    on debriefing (player_id);

create index if not exists fk_debriefing_user1_idx
    on debriefing (user_id);

create index if not exists fk_gn_topic1_idx
    on gn (topic_id);

alter table groupe
    add constraint FK_4B98C211F55203D
        foreign key (topic_id) references topic (id);

create table if not exists groupe_gn
(
    id              int auto_increment
        primary key,
    groupe_id       int          not null,
    gn_id           int          not null,
    responsable_id  int          null,
    discr           varchar(255) not null,
    free            tinyint(1)   not null,
    code            varchar(10)  null,
    jeu_maritime    tinyint(1)   null,
    jeu_strategique tinyint(1)   null,
    place_available int          null,
    constraint FK_413F11C7A45358C
        foreign key (groupe_id) references groupe (id),
    constraint FK_413F11CAFC9C052
        foreign key (gn_id) references gn (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists fk_groupe_gn_gn1_idx
    on groupe_gn (gn_id);

create index if not exists fk_groupe_gn_groupe1_idx
    on groupe_gn (groupe_id);

create index if not exists fk_groupe_gn_participant1_idx
    on groupe_gn (responsable_id);

create table if not exists participant
(
    id                       int auto_increment
        primary key,
    gn_id                    int          not null,
    user_id                  int unsigned not null,
    subscription_date        datetime     not null,
    discr                    varchar(255) not null,
    personnage_secondaire_id int          null,
    personnage_id            int          null,
    billet_id                int          null,
    billet_date              datetime     null,
    groupe_gn_id             int          null,
    valide_ci_le             datetime     null,
    constraint FK_D79F6B1144973C78
        foreign key (billet_id) references billet (id),
    constraint FK_D79F6B115E315342
        foreign key (personnage_id) references personnage (id),
    constraint FK_D79F6B11A76ED395
        foreign key (user_id) references user (id),
    constraint FK_D79F6B11AFC9C052
        foreign key (gn_id) references gn (id),
    constraint FK_D79F6B11E6917FB3
        foreign key (personnage_secondaire_id) references personnage_secondaire (id),
    constraint FK_D79F6B11FA640E02
        foreign key (groupe_gn_id) references groupe_gn (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

alter table groupe_gn
    add constraint FK_413F11C53C59D72
        foreign key (responsable_id) references participant (id);

create index if not exists fk_joueur_gn_gn1_idx
    on participant (gn_id);

create index if not exists fk_joueur_gn_user1_idx
    on participant (user_id);

create index if not exists fk_participant_billet1_idx
    on participant (billet_id);

create index if not exists fk_participant_groupe_gn1_idx
    on participant (groupe_gn_id);

create index if not exists fk_participant_personnage1_idx
    on participant (personnage_id);

create index if not exists fk_participant_personnage_secondaire1_idx
    on participant (personnage_secondaire_id);

create table if not exists participant_has_restauration
(
    id              int unsigned auto_increment
        primary key,
    participant_id  int          not null,
    restauration_id int          not null,
    date            datetime     not null,
    discr           varchar(255) not null,
    constraint FK_D2F2C8B47C6CB929
        foreign key (restauration_id) references restauration (id),
    constraint FK_D2F2C8B49D1C3019
        foreign key (participant_id) references participant (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists fk_participant_has_restauration_participant1_idx
    on participant_has_restauration (participant_id);

create index if not exists fk_participant_has_restauration_restauration1_idx
    on participant_has_restauration (restauration_id);

create table if not exists participant_potions_depart
(
    participant_id int not null,
    potion_id      int not null,
    primary key (participant_id, potion_id),
    constraint FK_D485198A5E315343
        foreign key (participant_id) references participant (id),
    constraint FK_D485198A7126B349
        foreign key (potion_id) references potion (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists IDX_D485198A5E315343
    on participant_potions_depart (participant_id);

create index if not exists IDX_D485198A7126B349
    on participant_potions_depart (potion_id);

create table if not exists personnage_background
(
    id            int auto_increment
        primary key,
    personnage_id int          not null,
    user_id       int unsigned not null,
    text          longtext     null,
    visibility    varchar(45)  null,
    creation_date datetime     not null,
    update_date   datetime     not null,
    discr         varchar(255) not null,
    gn_id         int          not null,
    constraint FK_273D6F455E315342
        foreign key (personnage_id) references personnage (id),
    constraint FK_273D6F45A76ED395
        foreign key (user_id) references user (id),
    constraint FK_273D6F45AFC9C052
        foreign key (gn_id) references gn (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists fk_personnage_background_gn1_idx
    on personnage_background (gn_id);

create index if not exists fk_personnage_background_personnage1_idx
    on personnage_background (personnage_id);

create index if not exists fk_personnage_background_user1_idx
    on personnage_background (user_id);

create table if not exists post
(
    id            int auto_increment
        primary key,
    topic_id      int          null,
    user_id       int unsigned not null,
    post_id       int          null,
    title         varchar(450) not null,
    text          longtext     not null,
    creation_date datetime     null,
    update_date   datetime     null,
    discr         varchar(255) not null,
    constraint FK_5A8A6C8D1F55203D
        foreign key (topic_id) references topic (id),
    constraint FK_5A8A6C8D4B89032C
        foreign key (post_id) references post (id),
    constraint FK_5A8A6C8DA76ED395
        foreign key (user_id) references user (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists fk_post_post1_idx
    on post (post_id);

create index if not exists fk_post_topic1_idx
    on post (topic_id);

create index if not exists fk_post_user1_idx
    on post (user_id);

create table if not exists post_view
(
    id      int auto_increment
        primary key,
    post_id int          not null,
    user_id int unsigned not null,
    date    datetime     not null,
    discr   varchar(255) not null,
    constraint FK_37A8CC854B89032C
        foreign key (post_id) references post (id),
    constraint FK_37A8CC85A76ED395
        foreign key (user_id) references user (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists fk_post_view_post1_idx
    on post_view (post_id);

create index if not exists fk_post_view_user1_idx
    on post_view (user_id);

create table if not exists religion
(
    id                     int auto_increment
        primary key,
    topic_id               int                  not null,
    label                  varchar(45)          not null,
    description            longtext             null,
    discr                  varchar(255)         not null,
    blason                 varchar(45)          null,
    description_orga       longtext             null,
    description_fervent    longtext             null,
    description_pratiquant longtext             null,
    description_fanatique  longtext             null,
    secret                 tinyint(1) default 0 not null,
    constraint FK_1055F4F91F55203D
        foreign key (topic_id) references topic (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create table if not exists personnage_religion_description
(
    personnage_id int not null,
    religion_id   int not null,
    primary key (personnage_id, religion_id),
    constraint FK_874677E25E315342
        foreign key (personnage_id) references personnage (id),
    constraint FK_874677E2B7850CBD
        foreign key (religion_id) references religion (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists IDX_874677E25E315342
    on personnage_religion_description (personnage_id);

create index if not exists IDX_874677E2B7850CBD
    on personnage_religion_description (religion_id);

create table if not exists personnages_religions
(
    id                int auto_increment
        primary key,
    religion_id       int          not null,
    religion_level_id int          not null,
    personnage_id     int          not null,
    discr             varchar(255) not null,
    constraint FK_8530B75F423EA53D
        foreign key (religion_level_id) references religion_level (id),
    constraint FK_8530B75F5E315342
        foreign key (personnage_id) references personnage (id),
    constraint FK_8530B75FB7850CBD
        foreign key (religion_id) references religion (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists fk_personnage_religion_religion1_idx
    on personnages_religions (religion_id);

create index if not exists fk_personnage_religion_religion_level1_idx
    on personnages_religions (religion_level_id);

create index if not exists fk_personnages_religions_personnage1_idx
    on personnages_religions (personnage_id);

create index if not exists fk_religion_topic1_idx
    on religion (topic_id);

create table if not exists religion_description
(
    id                int unsigned auto_increment
        primary key,
    religion_id       int          not null,
    religion_level_id int          not null,
    description       longtext     null,
    discr             varchar(255) not null,
    constraint FK_209A3DCE423EA53D
        foreign key (religion_level_id) references religion_level (id),
    constraint FK_209A3DCEB7850CBD
        foreign key (religion_id) references religion (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists fk_religion_description_religion1_idx
    on religion_description (religion_id);

create index if not exists fk_religion_description_religion_level1_idx
    on religion_description (religion_level_id);

create table if not exists religions_spheres
(
    sphere_id   int not null,
    religion_id int not null,
    primary key (sphere_id, religion_id),
    constraint FK_65855EBE75FD4EF9
        foreign key (sphere_id) references sphere (id),
    constraint FK_65855EBEB7850CBD
        foreign key (religion_id) references religion (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists IDX_65855EBE75FD4EF9
    on religions_spheres (sphere_id);

create index if not exists IDX_65855EBEB7850CBD
    on religions_spheres (religion_id);

create table if not exists reponse
(
    id             int unsigned auto_increment
        primary key,
    participant_id int          not null,
    question_id    int unsigned not null,
    reponse        varchar(45)  not null,
    discr          varchar(255) not null,
    constraint FK_5FB6DEC71E27F6BF
        foreign key (question_id) references question (id),
    constraint FK_5FB6DEC79D1C3019
        foreign key (participant_id) references participant (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists fk_reponse_idx
    on reponse (question_id);

create index if not exists fk_reponse_participant1_idx
    on reponse (participant_id);

create table if not exists secondary_group
(
    id                      int auto_increment
        primary key,
    secondary_group_type_id int          not null,
    personnage_id           int          null,
    topic_id                int          not null,
    label                   varchar(45)  not null,
    description             longtext     null,
    discr                   varchar(255) not null,
    description_secrete     longtext     null,
    secret                  tinyint(1)   null,
    materiel                longtext     null,
    constraint FK_717A91A31F55203D
        foreign key (topic_id) references topic (id),
    constraint FK_717A91A35E315342
        foreign key (personnage_id) references personnage (id),
    constraint FK_717A91A3B27217D1
        foreign key (secondary_group_type_id) references secondary_group_type (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create table if not exists intrigue_has_groupe_secondaire
(
    id                 int unsigned auto_increment
        primary key,
    intrigue_id        int unsigned not null,
    secondary_group_id int          not null,
    discr              varchar(255) not null,
    constraint FK_5C689C98631F6BDE
        foreign key (intrigue_id) references intrigue (id),
    constraint FK_5C689C9865F50501
        foreign key (secondary_group_id) references secondary_group (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists fk_intrigue_has_groupe_secondaire_intrigue1_idx
    on intrigue_has_groupe_secondaire (intrigue_id);

create index if not exists fk_intrigue_has_groupe_secondaire_secondary_group1_idx
    on intrigue_has_groupe_secondaire (secondary_group_id);

create table if not exists membre
(
    id                 int auto_increment
        primary key,
    personnage_id      int          not null,
    secondary_group_id int          not null,
    secret             tinyint(1)   null,
    discr              varchar(255) not null,
    constraint FK_F6B4FB295E315342
        foreign key (personnage_id) references personnage (id),
    constraint FK_F6B4FB2965F50501
        foreign key (secondary_group_id) references secondary_group (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists fk_personnage_groupe_secondaire_personnage1_idx
    on membre (personnage_id);

create index if not exists fk_personnage_groupe_secondaire_secondary_group1_idx
    on membre (secondary_group_id);

create table if not exists postulant
(
    id                 int auto_increment
        primary key,
    secondary_group_id int          not null,
    personnage_id      int          not null,
    date               datetime     null,
    explanation        longtext     not null,
    discr              varchar(255) not null,
    waiting            tinyint(1)   null,
    constraint FK_F79395125E315342
        foreign key (personnage_id) references personnage (id),
    constraint FK_F793951265F50501
        foreign key (secondary_group_id) references secondary_group (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists fk_postulant_personnage1_idx
    on postulant (personnage_id);

create index if not exists fk_postulant_secondary_group1_idx
    on postulant (secondary_group_id);

create index if not exists fk_secondary_group_personnage1_idx
    on secondary_group (personnage_id);

create index if not exists fk_secondary_group_topic1_idx
    on secondary_group (topic_id);

create index if not exists fk_secondary_groupe_secondary_group_type1_idx
    on secondary_group (secondary_group_type_id);

create table if not exists territoire
(
    id                   int auto_increment
        primary key,
    territoire_id        int          null,
    territoire_guerre_id int          null,
    appelation_id        int          not null,
    langue_id            int          null,
    topic_id             int          not null,
    religion_id          int          null,
    nom                  varchar(45)  not null,
    description          longtext     null,
    capitale             varchar(45)  null,
    politique            varchar(45)  null,
    dirigeant            varchar(45)  null,
    population           varchar(45)  null,
    symbole              varchar(45)  null,
    tech_level           varchar(45)  null,
    type_racial          longtext     null,
    inspiration          longtext     null,
    armes_predilection   longtext     null,
    vetements            longtext     null,
    noms_masculin        longtext     null,
    noms_feminin         longtext     null,
    frontieres           longtext     null,
    discr                varchar(255) not null,
    geojson              longtext     null,
    color                varchar(7)   null,
    groupe_id            int          null,
    tresor               int          null,
    resistance           int          null,
    blason               varchar(45)  null,
    description_secrete  longtext     null,
    statut               varchar(45)  null,
    culture_id           int unsigned null,
    ordre_social         int          not null,
    constraint FK_B8655F541F55203D
        foreign key (topic_id) references topic (id),
    constraint FK_B8655F542AADBACD
        foreign key (langue_id) references langue (id),
    constraint FK_B8655F54682CA693
        foreign key (territoire_guerre_id) references territoire_guerre (id),
    constraint FK_B8655F547A45358C
        foreign key (groupe_id) references groupe (id),
    constraint FK_B8655F54B108249D
        foreign key (culture_id) references culture (id),
    constraint FK_B8655F54B7850CBD
        foreign key (religion_id) references religion (id),
    constraint FK_B8655F54D0F97A8
        foreign key (territoire_id) references territoire (id),
    constraint FK_B8655F54F9E65DDB
        foreign key (appelation_id) references appelation (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create table if not exists chronologie
(
    id                int auto_increment
        primary key,
    zone_politique_id int          not null,
    description       longtext     not null,
    discr             varchar(255) not null,
    year              int          not null,
    month             int          null,
    day               int          null,
    visibilite        varchar(45)  not null,
    constraint FK_6ECC33A72FE85823
        foreign key (zone_politique_id) references territoire (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists fk_chronologie_zone_politique1_idx
    on chronologie (zone_politique_id);

alter table groupe
    add constraint FK_4B98C21D0F97A8
        foreign key (territoire_id) references territoire (id);

alter table personnage
    add constraint FK_6AEA486DD0F97A8
        foreign key (territoire_id) references territoire (id);

create table if not exists rumeur
(
    id            int unsigned auto_increment
        primary key,
    territoire_id int          null,
    user_id       int unsigned not null,
    text          longtext     not null,
    creation_date datetime     not null,
    update_date   datetime     not null,
    visibility    varchar(45)  not null,
    discr         varchar(255) not null,
    gn_id         int          not null,
    constraint FK_AD09D960A76ED395
        foreign key (user_id) references user (id),
    constraint FK_AD09D960AFC9C052
        foreign key (gn_id) references gn (id),
    constraint FK_AD09D960D0F97A8
        foreign key (territoire_id) references territoire (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists fk_rumeur_gn1_idx
    on rumeur (gn_id);

create index if not exists fk_rumeur_territoire1_idx
    on rumeur (territoire_id);

create index if not exists fk_rumeur_user1_idx
    on rumeur (user_id);

create index if not exists fk_territoire_appelation1_idx
    on territoire (appelation_id);

create index if not exists fk_territoire_culture1_idx
    on territoire (culture_id);

create index if not exists fk_territoire_groupe1_idx
    on territoire (groupe_id);

create index if not exists fk_territoire_langue1_idx
    on territoire (langue_id);

create index if not exists fk_territoire_religion1_idx
    on territoire (religion_id);

create index if not exists fk_territoire_territoire_guerre1_idx
    on territoire (territoire_guerre_id);

create index if not exists fk_territoire_topic1_idx
    on territoire (topic_id);

create index if not exists fk_zone_politique_zone_politique1_idx
    on territoire (territoire_id);

create table if not exists territoire_exportation
(
    territoire_id int not null,
    ressource_id  int not null,
    primary key (territoire_id, ressource_id),
    constraint FK_BC24449DD0F97A8
        foreign key (territoire_id) references territoire (id),
    constraint FK_BC24449DFC6CD52A
        foreign key (ressource_id) references ressource (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists IDX_BC24449DD0F97A8
    on territoire_exportation (territoire_id);

create index if not exists IDX_BC24449DFC6CD52A
    on territoire_exportation (ressource_id);

create table if not exists territoire_has_construction
(
    territoire_id   int not null,
    construction_id int not null,
    primary key (territoire_id, construction_id),
    constraint FK_FEB4D8E9CF48117A
        foreign key (construction_id) references construction (id),
    constraint FK_FEB4D8E9D0F97A8
        foreign key (territoire_id) references territoire (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists IDX_FEB4D8E9CF48117A
    on territoire_has_construction (construction_id);

create index if not exists IDX_FEB4D8E9D0F97A8
    on territoire_has_construction (territoire_id);

create table if not exists territoire_has_loi
(
    loi_id        int unsigned not null,
    territoire_id int          not null,
    primary key (territoire_id, loi_id),
    constraint FK_5470401BAB82AB5
        foreign key (loi_id) references loi (id),
    constraint FK_5470401BD0F97A8
        foreign key (territoire_id) references territoire (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists IDX_5470401BAB82AB5
    on territoire_has_loi (loi_id);

create index if not exists IDX_5470401BD0F97A8
    on territoire_has_loi (territoire_id);

create table if not exists territoire_importation
(
    territoire_id int not null,
    ressource_id  int not null,
    primary key (territoire_id, ressource_id),
    constraint FK_77B99CF6D0F97A8
        foreign key (territoire_id) references territoire (id),
    constraint FK_77B99CF6FC6CD52A
        foreign key (ressource_id) references ressource (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists IDX_77B99CF6D0F97A8
    on territoire_importation (territoire_id);

create index if not exists IDX_77B99CF6FC6CD52A
    on territoire_importation (ressource_id);

create table if not exists territoire_ingredient
(
    territoire_id int not null,
    ingredient_id int not null,
    primary key (territoire_id, ingredient_id),
    constraint FK_9B7BF292933FE08C
        foreign key (ingredient_id) references ingredient (id),
    constraint FK_9B7BF292D0F97A8
        foreign key (territoire_id) references territoire (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists IDX_9B7BF292933FE08C
    on territoire_ingredient (ingredient_id);

create index if not exists IDX_9B7BF292D0F97A8
    on territoire_ingredient (territoire_id);

create table if not exists territoire_langue
(
    territoire_id int not null,
    langue_id     int not null,
    primary key (territoire_id, langue_id),
    constraint FK_C9327BC32AADBACD
        foreign key (langue_id) references langue (id),
    constraint FK_C9327BC3D0F97A8
        foreign key (territoire_id) references territoire (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists IDX_C9327BC32AADBACD
    on territoire_langue (langue_id);

create index if not exists IDX_C9327BC3D0F97A8
    on territoire_langue (territoire_id);

create table if not exists territoire_religion
(
    territoire_id int not null,
    religion_id   int not null,
    primary key (territoire_id, religion_id),
    constraint FK_B23AB2D3B7850CBD
        foreign key (religion_id) references religion (id),
    constraint FK_B23AB2D3D0F97A8
        foreign key (territoire_id) references territoire (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists IDX_B23AB2D3B7850CBD
    on territoire_religion (religion_id);

create index if not exists IDX_B23AB2D3D0F97A8
    on territoire_religion (territoire_id);

create table if not exists titre_territoire
(
    id            int auto_increment
        primary key,
    titre_id      int          not null,
    territoire_id int          not null,
    label         varchar(45)  not null,
    discr         varchar(255) not null,
    constraint FK_FA93160ED0F97A8
        foreign key (territoire_id) references territoire (id),
    constraint FK_FA93160ED54FAE5E
        foreign key (titre_id) references titre (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists fk_titre_territoire_territoire1_idx
    on titre_territoire (territoire_id);

create index if not exists fk_titre_territoire_titre1_idx
    on titre_territoire (titre_id);

create index if not exists fk_topic_topic1_idx
    on topic (topic_id);

create index if not exists fk_topic_user1_idx
    on topic (user_id);

create index if not exists fk_user_etat_civil1_idx
    on user (etat_civil_id);

create index if not exists fk_user_personnage1_idx
    on user (personnage_id);

create index if not exists fk_user_personnage_secondaire1_idx
    on user (personnage_secondaire_id);

create table if not exists user_has_restriction
(
    user_id        int unsigned not null,
    restriction_id int          not null,
    primary key (user_id, restriction_id),
    constraint FK_1A57746A76ED395
        foreign key (user_id) references user (id),
    constraint FK_1A57746E6160631
        foreign key (restriction_id) references restriction (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists IDX_1A57746A76ED395
    on user_has_restriction (user_id);

create index if not exists IDX_1A57746E6160631
    on user_has_restriction (restriction_id);

create table if not exists watching_user
(
    post_id int          not null,
    user_id int unsigned not null,
    primary key (post_id, user_id),
    constraint FK_FFDC43024B89032C
        foreign key (post_id) references post (id),
    constraint FK_FFDC4302A76ED395
        foreign key (user_id) references user (id)
)
    engine = InnoDB
    collate = utf8mb3_unicode_ci;

create index if not exists IDX_FFDC43024B89032C
    on watching_user (post_id);

create index if not exists IDX_FFDC4302A76ED395
    on watching_user (user_id);

create table if not exists wt_heroisme_ad
(
    id            int auto_increment
        primary key,
    gn_id         int    not null,
    personnage_id int    not null,
    competence_id int(3) not null
)
    engine = MyISAM
    charset = utf8mb3;

create table if not exists wt_litterature_top
(
    id            int auto_increment
        primary key,
    gn_id         int    not null,
    personnage_id int    not null,
    Compétence    int(2) not null
)
    engine = MyISAM
    charset = utf8mb3;
