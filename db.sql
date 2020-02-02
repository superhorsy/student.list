create table user
(
    id       int auto_increment
        primary key,
    username varchar(20)  not null,
    name     varchar(50)  not null,
    email    varchar(320) not null,
    hash     varchar(255) not null,
    constraint user_login_uindex
        unique (username)
) collate = utf8mb4_general_ci;

create table tournament
(
    id            int auto_increment
        primary key,
    name          varchar(255)                                                not null,
    date          date                                                        null,
    owner_id      int                                                         not null,
    status        enum('awaiting', 'in progress', 'ended') default 'awaiting' not null,
    current_round int                                                         null,
    round_count   int                                                         null,
    toss          blob                                                        null comment 'serilization of TOSS method result',
    type          enum('1', '2')                                              null,
    prize_pool    bigint                                                      null,
    regions       json                                                        null,
    constraint tournament_user_id_fk
        foreign key (owner_id) references user (id)
            on update cascade on delete cascade
) collate = utf8mb4_general_ci;

create table players
(
    id            int auto_increment
        primary key,
    team          varchar(50)   null,
    nickname      varchar(50)   not null,
    tournament_id int           null,
    lifes         int(1) default 2 null,
    is_suspended  int           null,
    prize         bigint        null,
    region        varchar(255)  null,
    wins          int default 0 not null,
    games_played  int default 0 not null,
    constraint players_tournament_id_fk
        foreign key (tournament_id) references tournament (id)
            on update cascade on delete cascade
) collate = utf8mb4_general_ci;