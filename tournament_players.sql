create table players
(
    id       int auto_increment
        primary key,
    name     varchar(50)  not null,
    team     varchar(50)  null,
    contact  varchar(255) null,
    nickname varchar(50)  not null,
    constraint players_teams_team_name_fk
        foreign key (team) references teams (team_name)
            on update cascade on delete set null
);

INSERT INTO tournament.players (name, team, contact, nickname) VALUES ('Kirill', 'Dragon', null, 'SuperHorsy');
create table teams
(
    team_name  varchar(50) not null
        primary key,
    team_group char        null
);

INSERT INTO tournament.teams (team_name, team_group) VALUES ('Dragon', null);
create table tournament
(
    tournament_id int auto_increment
        primary key
);


create table user
(
    id    int auto_increment
        primary key,
    login varchar(50)  not null,
    name  varchar(50)  not null,
    email varchar(320) not null,
    constraint user_login_uindex
        unique (login)
);

