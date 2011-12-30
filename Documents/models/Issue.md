# Issue Model

````
<Issue Blob> issue.<issue_id>
<SortedSet> issue_list.<owner_id>.<repository_id>.<issue_status>
<SortedSet> pull_list.<owner_id>.<repoistory_id>.<issue_status>
<SortedSet> issue_labels.<owner_id>.<repository_id>.<label_id>.<issue_status>
<> issue_milestone.<owner_id>.<repository_id>.<milestone_id>.<issue_status>
<> issue_assignee.<owner_id>.<repository_id>.<issue_id>.<issue_status>
````
