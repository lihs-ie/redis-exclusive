#!/bin/bash

root_dir=$(git rev-parse --show-toplevel)
git_hooks_dir="$root_dir/.git/hooks"
hooks_dir="$root_dir/scripts"

for hook in $(ls ${hooks_dir}); do
    hook_path="$git_hooks_dir/$hook"
    if [ -f "$hook_path" ]; then
        echo "Overwriting existing hook: $hook_path"
    fi

    cp "$hooks_dir/$hook" "$hook_path"
    chmod +x "$hook_path"
    echo "Installed hook: $hook_path"
done
