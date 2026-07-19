# phpBB Style Tester [![Tests](https://github.com/vinny/phpbb-style-tester/actions/workflows/tests.yml/badge.svg)](https://github.com/vinny/phpbb-style-tester/actions) [![PHP Version](https://img.shields.io/badge/PHP-7.4%20to%208.3+-8892BF.svg)](https://packagist.org/packages/phpbb/phpbb) [![Why PHP](https://img.shields.io/badge/Why_PHP-in_2026-7A86E8?style=flat&labelColor=18181b)](https://whyphp.dev)



This tool automates the process of seeding a local phpBB development forum with comprehensive and edge-case template data. It is specifically designed to achieve **100% template coverage** for testing phpBB styles and verifying visual layouts.

> [!CAUTION]
> **DEVELOPMENT ONLY**: This tool performs direct database modifications and clears database cache structures. It must **only** be executed in local testing and staging environments. **Never run this utility on a production board!**

---

## 1. Features & Seeded Data Coverage

The tool executes specialized builders to cover the following template scenarios:

*   **Users (`Builders\UserBuilder`)**: Seeds exactly 25 test users mapped to default phpBB groups, with avatars and signatures configured.
*   **Forums (`Builders\ForumBuilder`)**: Creates test categories and forums (read, unread, locked, password-protected, and link redirects).
*   **Topics (`Builders\TopicBuilder`)**: Adds normal, sticky, announcement, global announcement, locked, and moved redirect topics.
*   **Posts (`Builders\PostBuilder`)**: Adds standard posts and replies to showcase BBCodes, smileys, deep quote nesting, and pagination.
*   **Polls (`Builders\PollBuilder`)**: Seeds active and voted polls with mock voter distribution to test results display.
*   **Attachments (`Builders\AttachmentBuilder`)**: Attaches test images and zip files to posts.
*   **Reports (`Builders\ReportBuilder`)**: Flags posts and topics to test the moderator queue layout.
*   **UCP (`Builders\PrivateMessageBuilder`)**: Populates the User Control Panel with read, unread, sent, and reported private messages, bookmarks, watches, and drafts.
*   **Notifications (`Builders\NotificationBuilder`)**: Inserts mock unread notifications to test header alert icons.
*   **Search Index (`Builders\SearchBuilder`)**: Automatically indexes seeded posts for test searches.



---

## 2. Security Guards & Restrictions

To prevent accidental data leakage or execution on live sites, the trigger script implements two strict security gates:

1.  **CLI SAPI Gate**: The script rejects execution if invoked through a web browser.
2.  **Localhost Gate**: Checks phpBB's `server_name` setting and restricts execution strictly to:
    *   `localhost` / `127.0.0.1` / `::1`
    *   Local development TLD suffixes: `.test`, `.local`, `.dev`, `.localhost`.

---

## 3. Installation & Usage

### Prerequisites
*   A **fresh** phpBB **3.3.x** local installation. Use [QuickInstall](https://www.phpbb.com/customise/db/official_tool/phpbb3_quickinstall/) to quickly set up fresh testing boards.
*   PHP **7.4** or above (CLI version enabled).

### Setup
Copy the `style_tester/` folder directly to the root of your local phpBB installation.

### Seed the Database
To run the seeding utility and populate your forum database, open a terminal in your phpBB root folder and execute:

```bash
php style_tester/run_tester.php
```

Upon success, you will see a detailed visual template coverage report.

---

## 4. Directory Structure

```
style_tester/
├── Assets/                 # Static images and testing assets
├── Builders/               # Query builders (User, Post, Forum, etc.)
├── Coverage/               # Checking and asserting seeded layouts
├── tests/                  # PHPUnit test cases and database mocks
├── StyleTesterInstaller.php   # Main seeder controller class
└── run_tester.php          # CLI entrypoint with security guards
```

---

## 5. Development & Testing

The tool includes a mock-based PHPUnit test suite to validate builder queries and dependencies. To execute it:

```bash
php phpunit.phar style_tester/tests/StyleTesterTest.php
```

---

## 6. License

[GPL-2.0](license.txt)

---

## 7. Assets Attribution

The seed assets (including default avatars and attachment images) utilize third-party creative resources:

*   Attachment Image: [Photo](https://unsplash.com/photos/pastel-spheres-on-gradient-background-PGdW_bHDbpI?utm_source=unsplash&utm_medium=referral&utm_content=creditCopyText) by [Milad Fakurian](https://unsplash.com/@fakurian?utm_source=unsplash&utm_medium=referral&utm_content=creditCopyText) on [Unsplash](https://unsplash.com/)
*   Avatars: Sourced from [Dicebear API](https://www.dicebear.com/)
*   Forum Icon: Sourced from [phpBB About Logos](https://www.phpbb.com/about/logos/)

