task :default do

end

task :sync do
  `rsync -avz --delete /Users/chobie/src/GitHQ/ 192.168.1.4:/home/chobie/githq.org/`
  `ssh 192.168.1.4 'cd /home/chobie/githq.org;rake copy_config'`
end

task :copy_config do
  `cp -f developer/config.xml app/config/`
end
