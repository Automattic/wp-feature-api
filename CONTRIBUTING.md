# Contributing to WP Feature API

## Building the Project

1. **Clone the repository:**

    ```bash
    git clone https://github.com/Automattic/wp-feature-api.git
    cd wp-feature-api
    ```

2. **Install dependencies:**
    This project uses npm workspaces. Dependencies are installed from the root directory.

    ```bash
    npm install
    ```

    ```bash
    composer install
    ```

3. **Build the project:**
    To build the JavaScript and CSS assets for the plugin and its packages:

    ```bash
    npm run build
    ```

## Release Process

Releasing a new version involves several steps:

1. **Ensure the `trunk` branch is stable and all changes for the release are merged.**

2. **Create a Branch and Push Changes:**
   Create a new branch for the release (e.g., `release/vX.Y.Z`).

    ```bash
    git checkout -b release/vX.Y.Z
    ```

3. **Update Version Numbers:**
    Manually update the version number in the following files to the new version (e.g., `1.2.3`):
    - [`package.json`](package.json): Update the `version` field.

        ```json
        {
          "name": "wp-feature-api",
          "version": "NEW_VERSION_HERE",
          // ...
        }
        ```

    - [`wp-feature-api.php`](wp-feature-api.php): Update the version in two places:
        - Plugin header comment:

            ```php
            /**
             * ...
             * Version: NEW_VERSION_HERE
             * ...
             */
            ```

        - PHP constant definition:

            ```php
            define( 'WP_FEATURE_API_VERSION', 'NEW_VERSION_HERE' );
            ```

4. **Commit Version Bump:**
    Commit these version changes with a message like `Update version to X.Y.Z`.

    ```bash
    git add package.json wp-feature-api.php
    git commit -m "Update version to X.Y.Z"
    ```

5. **Push Changes:**
    After committing the version bump, push the changes to the remote repository.

    ```bash
    git push origin release/vX.Y.Z
    ```

6. **Create a Pull Request:**
    Open a pull request from your `release/vX.Y.Z` branch to the `trunk` branch.

7. **Merge the Pull Request:**
    Once the pull request is approved, merge it into the `trunk` branch.

8. **Create a Git Tag (on `trunk`):**
    After the version bump PR is merged into `trunk`:
    1. Switch to your local `trunk` branch.
    2. Pull the latest changes (including the merge).
    3. Tag the merge commit on `trunk` with the new version number. The tag **must** match the pattern `vX.Y.Z` (e.g., `v1.2.3`) to trigger the release workflow.

    ```bash
    git checkout trunk
    git pull origin trunk
    # Ensure your local trunk is at the commit where the release PR was merged
    git tag vX.Y.Z
    git push origin vX.Y.Z
    ```

 You can also create a tag via the GitHub UI.

9. **Verify Release:**
    After the GitHub Actions workflow completes, check the "Releases" page on GitHub to ensure everything completed successfully.
