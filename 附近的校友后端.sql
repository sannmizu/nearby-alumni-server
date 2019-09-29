CREATE TABLE `users_account` (
`user_id` int(11) UNSIGNED NOT NULL,
`password` char(32) NOT NULL,
`salt` char(20) NOT NULL,
`tel` varchar(11) NULL COMMENT '当前绑定电话',
`email` varchar(30) NULL COMMENT '当前绑定邮箱',
PRIMARY KEY (`user_id`) ,
UNIQUE INDEX `users_account_tel` (`tel`) USING HASH,
UNIQUE INDEX `users_account_email` (`email`) USING HASH
);
CREATE TABLE `users_data` (
`user_id` int(11) UNSIGNED NOT NULL,
`nickname` varchar(20) NOT NULL,
`icon` mediumtext NULL COMMENT '用户头像存储路径',
`sign` varchar(30) NULL,
`sex` char(2) NULL,
`area_id` int(11) UNSIGNED NULL,
`career` varchar(20) NULL,
`constellation` char(4) NULL,
`age` int(3) NULL,
PRIMARY KEY (`user_id`) 
);
CREATE TABLE `users_root` (
`user_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '用户ID',
`regist_time` datetime NOT NULL COMMENT '注册时间',
`tel` varchar(11) NULL COMMENT '注册所用电话',
`email` varchar(30) NULL COMMENT '注册所用邮箱',
PRIMARY KEY (`user_id`) 
);
CREATE TABLE `area` (
`area_id` int(11) UNSIGNED NOT NULL,
`pid` int(11) UNSIGNED NULL,
`name` char(20) NOT NULL,
PRIMARY KEY (`area_id`) 
);
CREATE TABLE `user_follows` (
`user_id` int(11) UNSIGNED NOT NULL,
`follow_id` int(11) UNSIGNED NOT NULL,
`relation` int(3) NOT NULL,
PRIMARY KEY (`user_id`, `follow_id`) 
);
CREATE TABLE `comments` (
`comment_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '评论ID',
`repost` int(11) UNSIGNED NULL COMMENT '评论的评论的ID',
`author` int(11) UNSIGNED NULL,
`content` varchar(300) NULL,
`time` datetime NULL,
`num_likes` int(8) UNSIGNED NOT NULL,
PRIMARY KEY (`comment_id`) 
);
CREATE TABLE `mentions` (
`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
`from_id` int(11) UNSIGNED NOT NULL,
`to_id` int(11) UNSIGNED NOT NULL,
`comment_id` int(10) UNSIGNED NOT NULL,
PRIMARY KEY (`id`) 
);
CREATE TABLE `users_loc` (
`user_id` int(11) UNSIGNED NOT NULL,
`location` point NOT NULL,
`geohash` char(11) NOT NULL,
`time` datetime NOT NULL ON UPDATE CURRENT_TIMESTAMP,
PRIMARY KEY (`user_id`) ,
INDEX `users_loc_geohash` (`geohash`(6))
)ENGINE=MyISAM;
CREATE TABLE `connect` (
`user_id` int(11) UNSIGNED NOT NULL,
`conn_token` char(32) NOT NULL,
`aes_key` char(16) NOT NULL,
`aes_iv` char(16) NOT NULL,
`expire_time` datetime NOT NULL ON UPDATE CURRENT_TIMESTAMP,
PRIMARY KEY (`user_id`) ,
UNIQUE INDEX `connect_token` (`conn_token`) USING HASH
);
CREATE TABLE `posts` (
`post_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
`author` int(11) UNSIGNED NOT NULL,
`repost` int(11) UNSIGNED NULL,
`time` datetime NOT NULL ON UPDATE CURRENT_TIMESTAMP,
`title` char(30) NULL,
`content` varchar(500) NOT NULL,
`media` varchar(100) NULL,
`num_likes` int(8) UNSIGNED NOT NULL,
`num_reposts` int(8) UNSIGNED NOT NULL,
`area_id` int(11) UNSIGNED NOT NULL,
PRIMARY KEY (`post_id`) 
);
CREATE TABLE `post_comment` (
`post_id` int(11) UNSIGNED NOT NULL,
`comment_id` int(11) UNSIGNED NOT NULL,
PRIMARY KEY (`post_id`, `comment_id`) 
);
CREATE TABLE `posts_loc` (
`post_id` int(11) UNSIGNED NOT NULL,
`location` point NOT NULL,
`geohash` char(11) NOT NULL,
`time` datetime NOT NULL ON UPDATE CURRENT_TIMESTAMP,
PRIMARY KEY (`post_id`) ,
INDEX `posts_loc_geohash` (`geohash`(6))
)ENGINE=MyISAM;
CREATE TABLE `user_posts` (
`user_id` int(11) UNSIGNED NOT NULL,
`post_id` int(11) UNSIGNED NOT NULL,
`time` datetime NOT NULL ON UPDATE CURRENT_TIMESTAMP,
PRIMARY KEY (`user_id`, `post_id`) 
);
CREATE TABLE `log` (
`user_id` int(11) UNSIGNED NOT NULL,
`log_token` char(32) NOT NULL,
`expire_time` datetime NOT NULL ON UPDATE CURRENT_TIMESTAMP,
PRIMARY KEY (`user_id`) ,
UNIQUE INDEX `log_token` (`log_token`) USING HASH
);
CREATE TABLE `user_friends` (
`user_id` int(11) UNSIGNED NOT NULL,
`friend_id` int(11) UNSIGNED NOT NULL,
PRIMARY KEY (`user_id`, `friend_id`) 
);
CREATE TABLE `post_stars` (
`user_id` int(11) UNSIGNED NOT NULL,
`post_id` int(11) UNSIGNED NOT NULL,
PRIMARY KEY (`user_id`, `post_id`) 
);
CREATE TABLE `mipush` (
`user_id` int(11) UNSIGNED NOT NULL,
`regid` char(128) NOT NULL,
`alias` char(128) NULL,
PRIMARY KEY (`user_id`) 
);

ALTER TABLE `users_root` AUTO_INCREMENT=10000;
ALTER TABLE `users_account` ADD CONSTRAINT `fk_users_account_users_root_1` FOREIGN KEY (`user_id`) REFERENCES `users_root` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `users_data` ADD CONSTRAINT `fk_users_data_users_root_1` FOREIGN KEY (`user_id`) REFERENCES `users_root` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `users_data` ADD CONSTRAINT `fk_users_data_area_1` FOREIGN KEY (`area_id`) REFERENCES `area` (`area_id`) ON UPDATE CASCADE;
ALTER TABLE `user_follows` ADD CONSTRAINT `fk_user_follows_users_root_1` FOREIGN KEY (`user_id`) REFERENCES `users_root` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `mentions` ADD CONSTRAINT `fk_mentions_users_root_1` FOREIGN KEY (`from_id`) REFERENCES `users_root` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `mentions` ADD CONSTRAINT `fk_mentions_users_root_2` FOREIGN KEY (`to_id`) REFERENCES `users_root` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `mentions` ADD CONSTRAINT `fk_mentions_comments_1` FOREIGN KEY (`comment_id`) REFERENCES `comments` (`comment_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `user_follows` ADD CONSTRAINT `fk_user_follows_users_root_2` FOREIGN KEY (`follow_id`) REFERENCES `users_root` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `connect` ADD CONSTRAINT `fk_rsa_users_root_1` FOREIGN KEY (`user_id`) REFERENCES `users_root` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `posts` ADD CONSTRAINT `fk_posts_area_1` FOREIGN KEY (`area_id`) REFERENCES `area` (`area_id`) ON UPDATE CASCADE;
ALTER TABLE `post_comment` ADD CONSTRAINT `fk_post_comment_posts_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`post_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `post_comment` ADD CONSTRAINT `fk_post_comment_comments_1` FOREIGN KEY (`comment_id`) REFERENCES `comments` (`comment_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `user_posts` ADD CONSTRAINT `fk_user_posts_users_root_1` FOREIGN KEY (`user_id`) REFERENCES `users_root` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `user_posts` ADD CONSTRAINT `fk_user_posts_posts_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`post_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `log` ADD CONSTRAINT `fk_log_users_root_1` FOREIGN KEY (`user_id`) REFERENCES `users_root` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `user_friends` ADD CONSTRAINT `fk_user_friends_users_root_1` FOREIGN KEY (`user_id`) REFERENCES `users_root` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `user_friends` ADD CONSTRAINT `fk_user_friends_users_root_2` FOREIGN KEY (`friend_id`) REFERENCES `users_root` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `post_stars` ADD CONSTRAINT `fk_user_stars_users_root_1` FOREIGN KEY (`user_id`) REFERENCES `users_root` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `post_stars` ADD CONSTRAINT `fk_user_stars_posts_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`post_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `posts` ADD CONSTRAINT `fk_posts_posts_1` FOREIGN KEY (`repost`) REFERENCES `posts` (`post_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `comments` ADD CONSTRAINT `fk_comments_comments_1` FOREIGN KEY (`repost`) REFERENCES `comments` (`comment_id`) ON DELETE CASCADE ON UPDATE CASCADE;

