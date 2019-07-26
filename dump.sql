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

create table teams
(
    team_name  varchar(50) not null
        primary key,
    team_group char        null
);

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

create table tournament
(
    tournament_id int auto_increment
        primary key
);

INSERT INTO players (id, name, team, contact, nickname) VALUES (3, 'Kirill', 'Dragon', null, 'SuperHorsy');
create table teams
(
    team_name  varchar(50) not null
        primary key,
    team_group char        null
);

INSERT INTO teams (team_name, team_group) VALUES ('Dragon', null);
create table tournament
(
    tournament_id int auto_increment
        primary key
);

