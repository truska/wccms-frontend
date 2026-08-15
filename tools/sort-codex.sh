#!/bin/bash

echo "=== Sort-Codex: OpenAI Codex Diagnostics and Fix ==="
echo ""

# Check network connectivity to OpenAI
echo "1. Checking network connectivity to OpenAI..."
if ping -c 1 openai.com &> /dev/null; then
    echo "✓ Can reach openai.com"
else
    echo "✗ Cannot reach openai.com - Check internet connection"
fi

if ping -c 1 api.openai.com &> /dev/null; then
    echo "✓ Can reach api.openai.com"
else
    echo "✗ Cannot reach api.openai.com - Codex may not work"
fi

echo ""

# Check if curl can access OpenAI API (basic check)
echo "2. Testing OpenAI API access..."
if curl -s --max-time 5 https://api.openai.com/v1/models &> /dev/null; then
    echo "✓ OpenAI API accessible"
else
    echo "✗ OpenAI API not accessible - Firewall or proxy issue?"
fi

echo ""

# Check hostname resolution
echo "3. Checking hostname resolution..."
hostname=$(hostname)
echo "Server hostname: $hostname"
if nslookup $hostname &> /dev/null; then
    echo "✓ Hostname resolves"
else
    echo "✗ Hostname does not resolve - May cause 'Cannot resolve authority' error"
fi

echo ""

# Check for OpenAI Codex extensions on server...
CODEX_FOUND=false
# Check both server extensions and user extensions
for dir in /root/.vscode-server-insiders/cli/servers/Insiders-*/server/extensions/* /root/.vscode-server-insiders/extensions/*; do
    if [[ "$dir" == *"openai"* && -d "$dir" ]]; then
        CODEX_FOUND=true
        break
    fi
done

if $CODEX_FOUND; then
    echo "✓ OpenAI Codex/ChatGPT extension found"
else
    echo "✗ OpenAI Codex extension not found - Install it on the client"
fi

echo ""

echo "4. Manual steps to fix Codex:"
echo "   a) Ensure OpenAI Codex/ChatGPT extension is installed in VS Code"
echo "      - Open Extensions (Ctrl+Shift+X)"
echo "      - Search for 'OpenAI Codex' or 'ChatGPT', install if missing"
echo "   b) Sign in to OpenAI: Follow the extension's authentication prompts"
echo "   c) Reload VS Code window: Ctrl+Shift+P > 'Developer: Reload Window'"
echo "   d) Restart Extension Host: Ctrl+Shift+P > 'Developer: Restart Extension Host'"
echo "   e) For remote issues: Try 'Remote-SSH: Kill VS Code Server on Host' then reconnect"
echo ""

echo "5. For 'Cannot resolve authority' error:"
echo "   - Check if the remote host name resolves on your local machine"
echo "   - Add entry to /etc/hosts if necessary: <server_ip> $hostname"
echo "   - Ensure SSH config is correct"
echo "   - Try updating VS Code Insiders to the latest version"
echo ""

echo "6. If issues persist:"
echo "   - Check VS Code Developer Console (Help > Toggle Developer Tools)"
echo "   - Look for errors related to 'codex', 'openai', 'extension host', or 'authority'"
echo "   - Try disabling and re-enabling the OpenAI extension"
echo "   - Restart your local VS Code completely"
echo ""

echo "Run this script again after trying the steps above."