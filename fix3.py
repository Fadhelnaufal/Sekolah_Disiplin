import os, glob, re
for file in glob.glob('resources/js/Pages/**/Index.vue', recursive=True):
    with open(file, 'r') as f: content = f.read()
    
    content = content.replace('${id`);', '${id}`);')
    
    with open(file, 'w') as f: f.write(content)
