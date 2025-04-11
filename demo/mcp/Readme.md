# Testing the features API using an external MCP client

The features API does not have a builtin MCP server so we will use a proxy TS STDIO MCP server.

I've done all the tests using `claude code`, I had some problems using other MCP licnts like claude desktop (this one crashes), cursor does not wnat to comply with my requests/prompts, Windsurf is is kind of working but the free model used was acting a bit weird, probably using the paid version of windsurf will work better.

## Preparation

-   build the MCP server:
    -   go to `wp-content/plugins/wp-feature-api/demo/mcp/mcp-proxy` and run `npm i && npm run build`
    -   `wp-content/plugins/wp-feature-api/demo/mcp/mcp-proxy/dist/index.js` will act as a MCP proxy server. Use it on `wp-content/plugins/wp-feature-api/demo/mcp/.mcp.json`
    -   Activity log will be saved on `wp-content/plugins/wp-feature-api/demo/mcp/mcp-proxy/mcp-proxy.log`
-   install Clade Code https://docs.anthropic.com/en/docs/agents-and-tools/claude-code/overview
-   Change permalinks to postname, this is required by mcp-proxy as the url is hardcoded (/wp-admin/options-permalink.php)
-   Create an app password on /wp-admin/profile.php. If the option is not available for you add `define( 'WP_ENVIRONMENT_TYPE', 'local' );` on `wp-config.php`
-   Copy `wp-content/plugins/wp-feature-api/demo/mcp/.mcp.example.json` to `wp-content/plugins/wp-feature-api/demo/mcp/.mcp.json` and update the settings
    -   Use your username and the app passord generated above
-   in terminal `cd wp-content/plugins/wp-feature-api/demo/mcp` and run `claude`. This will detect your mcp configuration. Select `Yes, proceed with MCP servers enabled`
