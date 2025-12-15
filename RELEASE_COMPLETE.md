# Release Complete: v2.0.0 "Obsidian"

The release preparation is complete.

## Summary
-   **Version:** `v2.0.0`
-   **Codename:** Obsidian
-   **Focus:** Strict Types, DDD Structure, Documentation.

## Artifacts Created
1.  **CHANGELOG.md**: Updated with v2.0.0 details.
2.  **BREAKING_CHANGES.md**: Created with migration actions for namespaces and types.
3.  **RELEASE_NOTES.md**: Ready for GitHub Releases / Packagist.
4.  **PR_DESCRIPTION.md**: Standardized context for the merge.
5.  **Documentation**: Full suite generated in `/doc`.

## Next Steps (Maintainer)

Run the following commands to tag and publish:

```bash
# 1. Verification
git status
composer test

# 2. Commit Release Artifacts
git add CHANGELOG.md BREAKING_CHANGES.md RELEASE_NOTES.md PR_DESCRIPTION.md doc/
git commit -m "chore(release): prepare v2.0.0 Obsidian"

# 3. Tag
git tag -a v2.0.0 -m "Release v2.0.0 Obsidian"

# 4. Push
git push origin main --tags
```
