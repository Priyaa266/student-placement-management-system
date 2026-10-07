USE `student_placement_system`;

INSERT INTO `users` (`name`, `email`, `password`, `role`, `status`)
VALUES (
    'Placement Administrator',
    'admin@college.edu',
    '$2y$10$7du7WaiAlgMVkc36Is4N4.KG8sHyi8eN7.AUXW11D0vlhpzURkZ2a',
    'admin',
    'active'
)
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `password` = VALUES(`password`),
    `role` = 'admin',
    `status` = 'active';
