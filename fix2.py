import os, glob, re
for file in glob.glob('resources/js/Pages/**/Index.vue', recursive=True):
    with open(file, 'r') as f: content = f.read()
    
    # 1. Fix router.delete(`/path/${id
    content = re.sub(r'(router\.(?:delete|post)\(`/[a-zA-Z0-9-]+/\$\{id)\n', r'\1`);\n', content)
    
    # 2. Fix the specific semester post which looks like:
    # router.post(`/semesters/${id`);
    #         }
    #     })/close`);
    content = re.sub(r'(router\.post\(`/[a-zA-Z0-9-]+/\$\{id\)`\);\n\s*\}\n\s*\})(/close`\);)', r'\1}\2', content)
    
    with open(file, 'w') as f: f.write(content)
