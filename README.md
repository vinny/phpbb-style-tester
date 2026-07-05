# phpBB Style Tester

This tool automates the process of seeding a local phpBB development forum with comprehensive and edge-case template data. It is specifically designed to achieve **100% template coverage** for testing phpBB styles and verifying visual layouts.

> [!CAUTION]
> **DEVELOPMENT ONLY**: This tool performs direct database modifications and clears database cache structures. It must **only** be executed in local testing and staging environments. **Never run this utility on a production board!**

---

## 1. Features & Seeded Data Coverage

The tool executes specialized builders to cover the following template scenarios:

*   **Users (`Builders\UserBuilder`)**: Seeds exactly 25 test users (`tester_X` based on their `user_id`) mapped only to default standard phpBB groups (no new custom groups or ranks are created):
    *   `val_admin` (and the first two testers) are mapped to the default **Administrators** group.
    *   `val_glob_mod` (and the next two testers) are mapped to the default **Global Moderators** group.
    *   All other test users (referenced in logic as `val_reg_user`, `val_reg_user_2`, etc.) are mapped to the default **Registered Users** group.
    *   Configures user avatars (copied locally from Assets) and forum signatures.
*   **Forums (`Builders\ForumBuilder`)**: Creates a clean, nested structure:
    *   Category 1 & Category 2
    *   Lobby Forum (standard topics)
    *   Special Showcase (locked posts, attachment previews)
    *   Forum Link redirects
    *   Archive Forum
*   **Topics (`Builders\TopicBuilder`)**: Creates a variety of topic types:
    *   Normal, sticky, announcement, and global announcement topics.
    *   Locked and reported topics to validate moderator layout flags.
*   **Polls (`Builders\PollBuilder`)**: Inserts single-choice and multi-choice polls with various voter metrics.
*   **Attachments (`Builders\AttachmentBuilder`)**: Seeds image and document attachments to post previews.
*   **Reports (`Builders\ReportBuilder`)**: Flags topics and posts for visual testing of moderator queues.
*   **Private Messages (`Builders\PrivateMessageBuilder`)**: Populates the Admin's inbox (user ID 2) with:
    *   Unread, read, reported, and sent private messages.
    *   Sets bookmarks, topic watches, forum watches, and drafts.
*   **Notifications (`Builders\NotificationBuilder`)**: Inserts mock unread notifications to test the header alert icons.
*   **Search Index (`Builders\SearchBuilder`)**: Automatically builds search terms for seeded topics.

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
*   PHP **8.0** or above (CLI version enabled).

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

*   Attachment Image: [Photo](https://unsplash.com/pt-br/fotografias/esferas-pasteis-sobre-fundo-em-gradiente-PGdW_bHDbpI?utm_source=unsplash&utm_medium=referral&utm_content=creditCopyText) by [Milad Fakurian](https://unsplash.com/pt-br/@fakurian?utm_source=unsplash&utm_medium=referral&utm_content=creditCopyText) on [Unsplash](https://unsplash.com/)
*   Avatars: Sourced from [Dicebear API](https://www.dicebear.com/)

