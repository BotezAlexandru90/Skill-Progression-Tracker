# Skill Progression Tracker for WordPress

A WordPress plugin designed to track EVE Online skill training progression for players. The tracking interface is accessible within the WordPress admin dashboard and is visible only to users with **Administrator** or **Editor** roles.

---

## Features

- **Add Skill Plans:** Easily add new skill training plans for different players, including their name, a list of skills, and the total training time (Delta T).
- **AJAX-Powered Interface:** All actions—adding, editing, deleting, and updating status—are handled via AJAX, meaning the page never has to reload, providing a smooth and fast user experience.
- **View All Plans:** Displays all current and completed skill plans in a clean, sortable table within the WordPress admin area.
- **Mark as Completed:** Toggle the status of a skill plan between 'Active' and 'Completed'. Completed plans are visually distinguished with a strikethrough and different styling.
- **Inline Editing:** Edit any skill plan's details directly from the table view without navigating to a separate page.
- **Delete Entries:** Remove skill plans with a confirmation prompt to prevent accidental deletions.
- **Secure and Role-Based:** Access is restricted to users with the `edit_posts` capability (typically Administrators and Editors). All data handling is secured using WordPress nonces.
- **Self-Contained:** The plugin creates its own database table upon activation to store all tracking data, keeping it separate from standard WordPress tables.
- **User-Friendly UI:** Features include a collapsible form for adding new entries and a show/hide toggle for long skill lists to keep the main view tidy.

## Installation

1.  Download the plugin as a `.zip` file from this repository.
2.  Log in to your WordPress admin dashboard.
3.  Navigate to **Plugins** > **Add New**.
4.  Click the **Upload Plugin** button at the top of the page.
5.  Select the `.zip` file you downloaded and click **Install Now**.
6.  Once installed, click **Activate Plugin**.

Upon activation, the plugin will automatically create the necessary database table (`wp_skill_tracker`).

## How to Use

1.  After activating the plugin, a new menu item named **Skill Progression Tracker** will appear in your WordPress admin sidebar.
2.  Click on it to open the main tracking page.

### Adding a New Skill Plan

1.  Click the **Add New Skill Plan** button to reveal the input form.
2.  Fill in the following fields:
    -   **Player Name:** The name of the player whose skill plan you are tracking.
    -   **Skills (one per line):** The list of skills in the training queue.
    -   **Time Required (Delta T):** The total time for the plan (e.g., `3d 14h 22m`).
3.  Click the **Add Skill Plan** button. The new entry will instantly appear at the top of the table below.

### Managing Existing Skill Plans

In the "Current Skill Plans" table, you can perform the following actions for each entry:

-   **(Show/Hide Skills):** Click this link to toggle the visibility of the full skill list for that entry.
-   **Edit:** Click this to transform the row into an editable form. You can change the player name, skills, or Delta T. Click **Save Changes** to update or **Cancel** to revert.
-   **Mark Completed / Mark Active:** Click this button to toggle the status. The row's appearance will change accordingly.
-   **Delete:** Click this to permanently remove the entry. A confirmation dialog will appear first.

## Technical Details

-   **Database Table:** The plugin creates a custom table named `wp_skill_tracker` in your WordPress database to store all data.
-   **Permissions:** The plugin's admin page is restricted to users who have the `edit_posts` capability.
-   **Dependencies:** The plugin relies on the core WordPress AJAX API and jQuery, which is bundled with WordPress.

## License

This plugin is licensed under the **GPL v2 or later**.
See the [License URI](https://www.gnu.org/licenses/gpl-2.0.html) for more details.

---

_This plugin was developed by Surama Badasaz._
