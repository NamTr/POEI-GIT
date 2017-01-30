administerusersbyrole
=====================

Read INSTALL.txt to enable administerusersbyrole

SUMMARY
---

This module allows site builders to set up fine-grained permissions for
allowing "sub-admin" users to edit and delete other users ? more specific
than Drupal Core's all-or-nothing 'administer users' permission. It also
provides and enforces a 'create users' permission.

CORE PERMISSIONS
---

**Administer users**
  DO NOT set this for sub-admins.  This permission bypasses all of the
  permissions in "Administer Users by Role".

**View user information**
  Your sub-admins should probably have this permission.  (Most things work
  without it, but for example with a View showing users, the user name
  will only become a link if this permission is set.)

NEW PERMISSIONS
---

**Create new users**
  Create users, at admin/people/create. If in the account settings (/admin/config/people/accounts) the administrator select that Visitors can register accounts but administrator approval is required, the accounts created will be as blocked account. If the administrator select that Visitors can create accounts, the accounts created will be as active
  
***

**Edit users with no custom roles**
  Allows editing of any authenticated user that has no custom roles set.
  
***

**Edit users with no custom roles**
  Allows editing of any authenticated user with the specified role.
  To edit a user with multiple roles, the sub-admin must have permission to
  edit ALL of those roles.  ("Edit users with no custom roles" is NOT needed.)

***

**Edit users with {role} role: 'edit users with role {role}'**

Allows edit accounts that have a custom role that the administrator creates

***

The permission for cancel work exactly the same as those for edit.

NOTE FROM DRUPAL 7
----
'access users overview' permission removed in order to use just the 'access user profiles' permission declared in the module user


New funcionality
---
A panel to view users added in the path /users but to get the user names as links it's necesary to give the permission 'access user profiles'. In this way we can access to that user and use the paths /user/{user}/edit and /user/{user}/cancel.


GOOGLE CODE-IN
---------------
This module was ported as a Google Code-In (GCI) 2014 task by the student gvso. Google Code-in is a contest for pre-university students (e.g., high school and secondary school students ages 13-17) with the goal of encouraging young people to participate in open source. More info about GCI [https://developers.google.com/open-source/gci/](https://developers.google.com/open-source/gci/)
