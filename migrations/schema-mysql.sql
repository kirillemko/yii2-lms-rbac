-- drop table if exists `auth_assignment`;
-- drop table if exists `auth_item_child`;
-- drop table if exists `auth_item`;
-- drop table if exists `auth_rule`;


create table `permissions`
(
    `key` varchar(64) not null,
    primary key (`key`)
) engine InnoDB;

create table `groups_permissions`
(
    `group_id`        mediumint(8) unsigned not null,
    `permissions_key` varchar(64) not null,
    primary key (`group_id`, `permissions_key`),
    foreign key (`group_id`) references `groups` (`id`) on delete cascade on update cascade,
    foreign key (`permissions_key`) references `permissions` (`key`) on delete cascade on update cascade
) engine InnoDB;




