# Change Log Template

This template provides a standardized format for tracking file changes within project directories.

## Usage Instructions

1. **Copy the template section** below into your directory's README.md file
2. **Update the directory description** to match your folder's purpose
3. **Document baseline state** for existing files
4. **Log all changes** using the specified format
5. **Keep entries chronological** (newest first)

## Template

```markdown
# [Directory Name]

## Directory Purpose
[Brief description of what this directory contains and its role in the project]

## Change Log

### Format: YYYY-MM-DD | filename | change_type | description

### Recent Changes
- 2025-06-21 | example-file.php | BASELINE | Initial project state

### Change Types Legend
- **ADDED**: New file created
- **MODIFIED**: Existing file updated/edited
- **DELETED**: File removed from directory
- **RENAMED**: File moved or renamed
- **BASELINE**: Initial state documentation
- **REFACTORED**: Major structural changes to existing file
- **FIXED**: Bug fix or correction to existing file

### File Inventory
Current files in this directory:
- [List current files as of last update]

## [Additional Directory-Specific Content]
[Add any directory-specific documentation here]
```

## Best Practices

### Change Descriptions
- **Be specific**: "Updated user validation rules" vs "Modified file"
- **Include context**: "Added new test for database connectivity" 
- **Reference issues**: "Fixed bug #123 in login validation"
- **Note breaking changes**: "BREAKING: Changed API response format"

### Maintenance
- **Update immediately**: Log changes when they happen
- **Review regularly**: Ensure log accuracy during code reviews
- **Archive old entries**: Move entries older than 6 months to archive section
- **Standardize format**: Maintain consistent formatting across all directories

### Change Type Guidelines

#### ADDED
Use when creating completely new files. Include brief description of file purpose.
```
2025-06-21 | UserController.php | ADDED | Controller for user management endpoints
```

#### MODIFIED  
Use for updates to existing files. Specify what was changed.
```
2025-06-21 | UserController.php | MODIFIED | Added password reset functionality
```

#### DELETED
Use when removing files. Briefly explain why.
```
2025-06-21 | OldUserHelper.php | DELETED | Replaced by UserService class
```

#### RENAMED
Use when moving or renaming files. Show old and new names.
```
2025-06-21 | UserHelper.php → UserService.php | RENAMED | Better naming convention
```

#### REFACTORED
Use for major structural changes that maintain functionality.
```
2025-06-21 | UserController.php | REFACTORED | Extracted validation logic to separate service
```

#### FIXED
Use for bug fixes and corrections.
```
2025-06-21 | UserController.php | FIXED | Resolved null pointer exception in login method
```

## Integration with Development Workflow

### Git Integration
Change logs complement git history by providing:
- **Business context** for technical changes
- **Impact assessment** for reviewers
- **Quick reference** without git log diving

### Code Reviews
Include change log updates in pull requests:
- **Verify accuracy**: Ensure logged changes match actual modifications
- **Check completeness**: Confirm all significant changes are documented
- **Validate format**: Maintain consistent formatting standards

### Release Management
Use change logs to:
- **Generate release notes**: Extract user-facing changes
- **Track technical debt**: Identify areas needing attention
- **Plan migrations**: Understand breaking changes over time

## Example Implementation

Here's a complete example for a models directory:

```markdown
# App Models

## Directory Purpose
Contains Eloquent models for the PantryCRM application, defining data structures and relationships for organizations, contacts, interactions, and opportunities.

## Change Log

### Format: YYYY-MM-DD | filename | change_type | description

### Recent Changes
- 2025-06-21 | Principal.php | ADDED | Model for food service principals/suppliers
- 2025-06-21 | ProductLine.php | ADDED | Model for principal product lines
- 2025-06-21 | Organization.php | MODIFIED | Added priority label accessor and scopes
- 2025-06-21 | Contact.php | MODIFIED | Enhanced with full name accessor and relationship scopes
- 2025-06-21 | Opportunity.php | REFACTORED | Added comprehensive stage/value formatting

### Change Types Legend
[Standard legend as defined above]

### File Inventory
Current files in this directory:
- Account.php
- Contact.php  
- Contract.php
- Interaction.php
- Lead.php
- Opportunity.php
- Organization.php
- Principal.php (NEW)
- ProductLine.php (NEW)
- Session.php
- SystemSetting.php
- User.php
- VerificationToken.php

## Model Relationships Overview
[Additional model-specific documentation]
```