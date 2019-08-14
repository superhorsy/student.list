create table players
(
    id            int auto_increment
        primary key,
    team          char        null,
    nickname      varchar(50) not null,
    tournament_id int         null,
    constraint players_tournament_id_fk
        foreign key (tournament_id) references tournament (id)
            on update cascade on delete cascade
);

INSERT INTO tournament.players (`group`, nickname, tournament_id) VALUES ('A', 'SuperHorsy', null);
INSERT INTO tournament.players (`group`, nickname, tournament_id) VALUES (null, 'asdf', 6);
INSERT INTO tournament.players (`group`, nickname, tournament_id) VALUES (null, 'SuperHorsy', 7);
INSERT INTO tournament.players (`group`, nickname, tournament_id) VALUES (null, 'asdf', 7);
INSERT INTO tournament.players (`group`, nickname, tournament_id) VALUES (null, 'Victor222', 7);
INSERT INTO tournament.players (`group`, nickname, tournament_id) VALUES (null, 'Sanyok', 7);
create table tournament
(
    id       int auto_increment
        primary key,
    name     varchar(255) not null,
    datetime datetime     null,
    owner_id int          not null,
    constraint tournament_user_id_fk
        foreign key (owner_id) references user (id)
            on update cascade on delete cascade
);

INSERT INTO tournament.tournament (name, datetime, owner_id) VALUES ('Tournament', '2020-02-10 20:20:00', 14);
INSERT INTO tournament.tournament (name, datetime, owner_id) VALUES ('Tournament', '2020-02-10 20:20:00', 14);
INSERT INTO tournament.tournament (name, datetime, owner_id) VALUES ('Tournament', '2020-02-10 20:20:00', 14);
INSERT INTO tournament.tournament (name, datetime, owner_id) VALUES ('Tournament', '2020-02-20 20:20:00', 14);
INSERT INTO tournament.tournament (name, datetime, owner_id) VALUES ('Tournament', '2020-02-20 20:20:00', 14);
INSERT INTO tournament.tournament (name, datetime, owner_id) VALUES ('Tournament', '2020-02-20 12:12:00', 14);
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
);

INSERT INTO tournament.user (username, name, email, hash) VALUES ('test', 'test', 'test@test.ru', '$2y$10$.S9UL//SxnMQWp.5K867fumxf0oiK1.SlAEe0EMvplOuIRrGdknva');