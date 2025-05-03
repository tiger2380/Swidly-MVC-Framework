CREATE DATABASE IF NOT EXISTS posts;

use posts;

create table post
(
    title     varchar(255) null,
    id        int auto_increment
        primary key,
    createdAt varchar(255) not null,
    body      longtext     null
)
    auto_increment = 6;

INSERT INTO posts.post (title, id, createdAt, body) VALUES ('PostModel Title 1', 1, '2022-11-11 12:11:52', 'This is the post body');
INSERT INTO posts.post (title, id, createdAt, body) VALUES ('PostModel Title 2', 2, '2022-11-11 12:35:50', 'This is my second post... YAY!!');
INSERT INTO posts.post (title, id, createdAt, body) VALUES ('New Title', 3, '2022-11-11 02:46:00', 'This is a newly body.');
INSERT INTO posts.post (title, id, createdAt, body) VALUES ('New Title', 4, '2022-11-11 02:46:00', 'This is a newly body.');
INSERT INTO posts.post (title, id, createdAt, body) VALUES ('test234432', 5, '2022-11-11 02:46:00', 'fasd fa sdfa sdf asd');
INSERT INTO posts.post (title, id, createdAt, body) VALUES ('Hello World!!', 6, '2022-11-11 02:46:00', 'Hello everyone. welcome to my page.');
