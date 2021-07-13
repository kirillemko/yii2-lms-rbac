drop table if exists `permissions`;
drop table if exists `groups_permissions`;

create table `permissions`
(
    `key`  varchar(64)  not null,
    `name` varchar(128) not null,
    primary key (`key`)
) engine InnoDB;

insert into permissions (`key`, `name`)
values ('permissions.see_all', 'Permissions. See'),
       ('permissions.manage', 'Permissions. Manage'),
       ('permissions.manage_groups', 'Permissions. Manage groups');



create table `groups_permissions`
(
    `group_id`       mediumint(8) unsigned not null,
    `permission_key` varchar(64) not null,
    primary key (`group_id`, `permissions_key`),
    foreign key (`group_id`) references `groups` (`id`) on delete cascade on update cascade,
    foreign key (`permission_key`) references `permissions` (`key`) on delete cascade on update cascade
) engine InnoDB;

insert into groups_permissions (`group_id`, `permission_key`)
values (1, 'permissions.see'),
       (1, 'permissions.manage'),
       (1, 'permissions.manage_groups');

