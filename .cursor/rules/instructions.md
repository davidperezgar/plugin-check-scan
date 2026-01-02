This plugins is an Addon of Plugin Check Plugin (https://github.com/WordPress/plugin-check). The main objective is to scan the whole installation of WordPress with PCP, and make an score of the results. 

What we want is to audit the actual installation of WordPress.

What we will need:
- A page in Tools menu to run Score
- Show a widget in Dashboard so we can show the score

Page Scanner Will be:
- A widget with the latest score.
- Show a history from latests runs
- Run so you can make an scan for every plugin in the page.
- Use PCP with error-severity=7 warning-severity=6 include-low-severity-errors options.
- Make an score for every plugin based on that result
- Show the result of every plugin as an accordion so you can see a resume with the score and click to see more results.

