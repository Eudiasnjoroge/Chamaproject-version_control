/chama_app/
├── assets/
│   ├── css/          # CSS files
│   ├── js/           # JavaScript files
│   ├── images/       # Image assets
│   └── uploads/      # User uploads (payment proofs)
├── config/
│   ├── database.php  # DB connection
│   └── constants.php # App constants
├── includes/
│   ├── auth.php      # Authentication functions
│   ├── functions.php # Helper functions
│   └── header.php    # Common header
├── classes/          # PHP classes
│   ├── User.php
│   ├── Chama.php
│   ├── Contribution.php
│   └── Notification.php
├── pages/
│   ├── auth/
│   │   ├── login.php
│   │   └── register.php
│   ├── dashboard.php
│   ├── chamas/
│   │   ├── create.php
│   │   ├── view.php
│   │   └── manage.php
│   ├── contributions/
│   │   ├── make.php
│   │   └── history.php
│   └── admin/        # Admin panel
└── index.php         # Main entry point