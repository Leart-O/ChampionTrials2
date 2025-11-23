# Authority User Setup Guide

## How Authority Login Works

When you create an Authority, you're actually creating **two separate things**:

1. **Authority Record** - The organizational entity (e.g., "Road Maintenance Department")
2. **User Account** - The login credentials for someone to access the system as that authority

### Login Process

- **Username**: The username you provide when creating the authority
- **Password**: The password you provide when creating the authority
- **NOT the authority name**: The authority name is just metadata, not a login credential

### Example

When creating an authority:
- Authority Name: "Road Maintenance Department"
- Username: "road_maint" ← **This is what they use to log in**
- Password: "SecurePass123!" ← **This is what they use to log in**

The user logs in with:
- Username: `road_maint`
- Password: `SecurePass123!`

## Database Setup

**IMPORTANT**: Before using Authority features, you must run the migration:

1. Open phpMyAdmin
2. Select your `citycare` database
3. Go to SQL tab
4. Run the contents of `migrations/add_authority_role.sql`:
   ```sql
   -- Add Authority role
   INSERT INTO `user_roles` (`role_id`, `role_name`) VALUES (4, 'Authority') 
   ON DUPLICATE KEY UPDATE role_name = 'Authority';
   
   -- Add user_id column to authorities table
   ALTER TABLE `authorities` 
   ADD COLUMN `user_id` INT(11) DEFAULT NULL AFTER `id`,
   ADD KEY `user_id` (`user_id`),
   ADD CONSTRAINT `authorities_ibfk_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;
   ```

## Creating Authorities

### From Admin Panel
1. Go to Admin Panel
2. In the "Users" section, fill in:
   - Username
   - Email
   - Password
   - Role: Select "Authority"
3. Click the "+" button to create the user
4. Then create the authority record separately in the "Manage Authorities" section

### From Municipality Dashboard
1. Go to Municipality Dashboard
2. Scroll to "Create New Authority" section
3. Fill in:
   - Authority Name (e.g., "Road Maintenance")
   - Type (e.g., "Department")
   - Contact Email
   - Notes (optional)
   - **User Account Details:**
     - Username (for login)
     - User Email
     - Password (for login)
4. Click "Create Authority"

This creates both the authority record AND the user account, and links them together automatically.

## Troubleshooting

### "ERR_TOO_MANY_REDIRECTS" Error

This happens if:
1. The migration hasn't been run (user_id column doesn't exist)
2. The authority user account isn't linked to an authority record

**Solution:**
1. Run the migration SQL (see above)
2. Make sure when creating authorities, you provide both the authority details AND user account details
3. The system will automatically link them

### "Your user account is not linked to an authority"

This means the user account exists but isn't connected to an authority record.

**Solution:**
1. Find the user_id of the authority user
2. Find the authority_id that should be linked
3. Run this SQL:
   ```sql
   UPDATE authorities SET user_id = [user_id] WHERE id = [authority_id];
   ```

Or recreate the authority using the Municipality Dashboard form which handles linking automatically.

