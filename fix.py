import os, glob

for file in glob.glob('resources/js/Pages/**/Index.vue', recursive=True):
    with open(file, 'r') as f: content = f.read()
    
    if '})`);' in content:
        content = content.replace('})`);', '});')
        with open(file, 'w') as f: f.write(content)
        print('Fixed syntax error in ' + file)
