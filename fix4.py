import os, glob
for file in glob.glob('resources/js/Pages/**/Index.vue', recursive=True):
    with open(file, 'r') as f: content = f.read()
    
    content = content.replace("    });\n    }\n};", "    });\n};")
    content = content.replace("    });\n    }\n</script>", "    });\n</script>")
    
    with open(file, 'w') as f: f.write(content)
