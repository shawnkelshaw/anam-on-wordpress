#!/bin/bash
# EMERGENCY ROLLBACK SCRIPT
# Run this if the refactoring breaks anything
# Usage: bash ROLLBACK.sh

echo "🚨 ROLLING BACK TO PRE-REFACTOR STATE..."

# Switch back to main branch
git checkout main

echo "✅ Rolled back to main branch"
echo "Your working code is restored!"
echo ""
echo "To see the refactor attempt later, run: git checkout refactor-js-extraction-phase1"
