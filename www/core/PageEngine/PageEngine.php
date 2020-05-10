<?php

require 'TagItemType.php';
require 'BaseComponent.php';
require 'ComponentInfo.php';
require 'ComponentRenderer.php';
require 'PageTemplate.php';
require 'TagItem.php';

class PageEngine
{
    private string $sourcePath;
    private string $buildPath;
    private string $rootComponent;

    /** @var ComponentInfo[] */
    private array $components;

    /** @var mixed[] */
    private array $tokens;

    /** @var PageTemplate[] */
    private array $templates;

    /** @var string[string] */
    private array $reservedTags;

    private string $reservedTagsString = 'html,body,base,head,link,meta,style,title,' .
        'address,article,aside,footer,header,h1,h2,h3,h4,h5,h6,hgroup,nav,section,' .
        'div,dd,dl,dt,figcaption,figure,picture,hr,img,li,main,ol,p,pre,ul,' .
        'a,b,abbr,bdi,bdo,br,cite,code,data,dfn,em,i,kbd,mark,q,rp,rt,rtc,ruby,' .
        's,samp,small,span,strong,sub,sup,time,u,var,wbr,area,audio,map,track,video,' .
        'embed,object,param,source,canvas,script,noscript,del,ins,' .
        'caption,col,colgroup,table,thead,tbody,td,th,tr,' .
        'button,datalist,fieldset,form,input,label,legend,meter,optgroup,option,' .
        'output,progress,select,textarea,' .
        'details,dialog,menu,menuitem,summary,' .
        'content,element,shadow,template,blockquote,iframe,tfoot' .
        'svg,animate,circle,clippath,cursor,defs,desc,ellipse,filter,font-face,' .
        'foreignObject,g,glyph,image,line,marker,mask,missing-glyph,path,pattern,' .
        'polygon,polyline,rect,switch,symbol,text,textpath,tspan,use,view,template';

    /** @var string[string] */
    private array $selfClosingTags;

    private string $selfClosingTagsString = 'area,base,br,col,command,embed,hr' .
        ',img,input,keygen,link,menuitem,meta,param,source,track,wbr';

    public function __construct(string $sourcePath, string $buildPath, string $rootComponent)
    {
        $this->sourcePath = $sourcePath;
        $this->buildPath = $buildPath;
        $this->rootComponent = $rootComponent;
        $this->components = [];
        $this->tokens = [];
        $this->templates = [];
        $this->reservedTags = array_flip(explode(',', $this->reservedTagsString));
        $this->selfClosingTags = array_flip(explode(',', $this->selfClosingTagsString));
    }
    function startApp(): void
    {
        $this->Compile();
        if (!isset($this->components[$this->rootComponent])) {
            throw new Exception("Component {$this->rootComponent} is missing!");
        }
        $root = $this->components[$this->rootComponent];
        $rootApp = new ComponentRenderer();
        $rootApp->component = new $root->name();

        //$this->debug($rootApp);
        //$this->debug($this->rootComponent . ' ' . $this->path);
        //$this->debug($this->templates);
        //$this->debug($this->components);
        //$this->debug($this->tokens);
    }

    function Compile(): void
    {
        $pages = $this->getDirContents($this->sourcePath);
        foreach (array_keys($pages) as $filename) {
            $pathinfo = pathinfo($filename);
            if ($pathinfo['extension'] === 'php') {
                $pathWOext = $pathinfo['dirname'] . DIRECTORY_SEPARATOR . $pathinfo['filename'];
                $templatePath = $pathWOext . '.html';
                $componentInfo = new ComponentInfo();
                $componentInfo->fullpath = $filename;
                if (isset($pages[$templatePath])) {
                    $componentInfo->templatePath = $templatePath;
                }
                $tokens = token_get_all(file_get_contents($filename), TOKEN_PARSE);
                $className = '';
                $nextStringIsClass = false;
                foreach ($tokens as &$token) {
                    if (is_int($token[0])) {
                        $token[] = token_name($token[0]);
                        if ($token[0] == T_CLASS) {
                            $nextStringIsClass = true;
                        }
                        if ($nextStringIsClass && $token[0] == T_STRING) {
                            if (empty($className)) {
                                $className = $token[1];
                            }
                            $nextStringIsClass = false;
                        }
                    }
                }
                $componentInfo->name = $className;
                $componentInfo->tag = $className;

                if (!empty($className)) {
                    $this->components[$className] = $componentInfo;
                    $this->tokens[$className] = $tokens;
                }
            }
        }
        foreach ($this->components as $className => &$componentInfo) {
            $this->templates[$className] = $this->compileTemplate($componentInfo->templatePath);
        }
        $this->build($this->templates[$this->rootComponent]);
    }

    function compileTemplate(string $path)
    {
        $template = new PageTemplate();
        if (empty($path)) {
            throw new Exception("Argument `\$path` is missing");
        }
        $template->Path = $path;
        $text = file_get_contents($path);
        $raw = str_split($text);
        $template->RootTag = new TagItem();
        $currentParent = &$template->RootTag;
        $currentType = new TagItemType(TagItemType::TextContent);
        $nextType = new TagItemType(TagItemType::TextContent);
        $content = '';
        $saveContent = false;
        $nextIsExpression = false;
        $itsExpression = false;
        $itsBlockExpression = false;
        $blocksCount = 0;
        $skipInExpression = 0;
        $detectedQuoteChar = false;
        $skipCount = 0;
        $length = count($raw);
        $i = 0;
        $goDown = false;
        $goUp = false;
        $waitForTagEnd = false;
        while ($i < $length) {
            $char = $raw[$i];
            if (!$itsBlockExpression) {
                switch ($char) {
                    case '<': {
                            if ($currentType->Name === TagItemType::TextContent) {
                                $nextType = new TagItemType(TagItemType::Tag);
                                $skipCount = 1;
                                $saveContent = true;
                                break;
                            }
                        }
                    case '>': {
                            if ($waitForTagEnd) {
                                $waitForTagEnd = false;
                                $skipCount = 1;
                                $nextType = new TagItemType(TagItemType::TextContent);
                                $goUp = true;
                                $saveContent = true;
                                break;
                            }
                            if ($currentType->Name !== TagItemType::TextContent) {
                                $nextType = new TagItemType(TagItemType::TextContent);
                                $skipCount = 1;
                                $saveContent = true;

                                if ($currentType->Name === TagItemType::Tag) {
                                    $goDown = true;
                                }
                                break;
                            }
                        }
                    case '/': {
                            if ($currentType->Name === TagItemType::Tag) { // <tag/> or </tag>
                                $skipCount = 1;
                                if (empty($content) || ctype_space($content)) { // </tag> closing tag
                                    // ignore next untill '>'
                                    $waitForTagEnd = true;
                                } else { // <tag/> selfClosingTag
                                    $nextType = new TagItemType(TagItemType::TextContent);
                                    $skipCount = 1;
                                    $saveContent = true;
                                    $waitForTagEnd = true;
                                    $goDown = true;
                                }
                                break;
                            }
                            //<tag attr.. /> or <tag />
                            if ($currentType->Name === TagItemType::Attribute) {
                                $skipCount = 1;
                                $waitForTagEnd = true;
                                $goUp = true;
                                $saveContent = true;
                            }
                        }
                    case '=': {
                            if ($currentType->Name === TagItemType::Attribute) {
                                $skipCount = 1;
                                $saveContent = true;
                                $nextType = new TagItemType(TagItemType::AttributeValue);
                                $goDown = true;
                                break;
                            }
                        }
                    case "'":
                    case '"': {
                            if ($currentType->Name === TagItemType::AttributeValue) {
                                if ($detectedQuoteChar) {
                                    if ($detectedQuoteChar === $char) { // end of value, closing quote " or '
                                        $detectedQuoteChar = false;
                                        $saveContent = true;
                                        $nextType = new TagItemType(TagItemType::Attribute);
                                        $goUp = true;
                                        $skipCount = 1;
                                    }
                                } else { // begin "attr value"
                                    $detectedQuoteChar = $char;
                                    $skipCount = 1;
                                }
                                break;
                            }
                        }
                    case '{': {
                            $itsBlockExpression = true;
                            $skipCount = 1;
                            $skipInExpression = 1;
                            $saveContent = true;
                            $nextIsExpression = true;
                            $saveContent = true;
                            break;
                        }
                    case '$': {
                            $nextIsExpression = true;
                            $saveContent = true;
                            break;
                        }
                    default: {
                            if (ctype_space($char)) {
                                if (
                                    $currentType->Name === TagItemType::Tag
                                    || $currentType->Name === TagItemType::Attribute
                                ) { // '<tag attribute="value"'
                                    $skipCount = 1;
                                    $nextType = new TagItemType(TagItemType::Attribute);
                                    $saveContent = true;
                                    if ($currentType->Name === TagItemType::Tag) {
                                        $goDown = true;
                                    }
                                    break;
                                }
                            }
                            if ($itsExpression) {
                                if (!ctype_alnum($char)) {
                                    $saveContent = true;
                                }
                            }
                        }
                } // end of switch
            } else { // $itsBlockExpression === true
                if ($skipInExpression > 0) {
                    $skipInExpression--;
                } else {
                    switch ($char) {
                        case '{': {
                                $blocksCount++;
                                break;
                            }
                        case '}': {
                                if ($blocksCount > 0) {
                                    $blocksCount--;
                                } else { // end of expression
                                    $itsBlockExpression = false;
                                    $skipCount = 1;
                                    $saveContent = true;
                                }
                                break;
                            }
                    }
                }
            }
            if ($waitForTagEnd) {
                $skipCount = 1;
            }
            if ($saveContent) {
                if (!empty($content)) {
                    $child = $currentParent->newChild();
                    $child->Type = $currentType;
                    $child->Content = $content;
                    $child->ItsExpression = $itsExpression;

                    if ($currentType->Name === TagItemType::Tag && !$itsExpression) {
                        if (
                            !strpos($content, ':')
                            && !isset($this->reservedTags[strtolower($content)])
                        ) {
                            if (!isset($this->components[$content])) {
                                throw new Exception("Component `$content` not found.");
                            }
                            $child->Type = new TagItemType(TagItemType::Component);
                        }
                    }
                }
                $itsExpression = false;
                if ($nextIsExpression) {
                    $nextIsExpression = false;
                    $itsExpression = true;
                }
                $saveContent = false;
                $currentType = $nextType;
                $content = '';
                if ($goDown && !$goUp) {
                    if ($currentParent->getChildren()) {
                        $currentParent = &$currentParent->currentChild();
                    } else {
                        echo 'Can\'t get child, silent exit';
                        break;
                    }
                }
                if ($goUp && !$goDown) {
                    if ($currentParent->parent()) {
                        $currentParent = &$currentParent->parent();
                    } else {
                        echo 'Can\'t get parent, silent exit';
                        break;
                    }
                }
                $goDown = false;
                $goUp = false;
            }


            if ($skipCount > 0) {
                $skipCount--;
            } else {
                $content .= $char;
            }
            // end of while
            $i++;
        }

        $template->RootTag->cleanParents();
        return $template;
    }

    function build(PageTemplate &$pageTemplate): void
    {
        $html = '';
        $this->buildInternal($pageTemplate, $html);
        $this->debug(htmlentities($html));
        $this->debug($pageTemplate);
    }
    private function buildInternal(PageTemplate &$pageTemplate, string &$html): void
    {
        foreach ($pageTemplate->RootTag->getChildren() as &$tag) {
            $this->buildTag($tag, $html);
        }
    }
    function buildTag(TagItem &$tagItem, string &$html): void
    {
        $replaceByTag = 'div';
        /** @var TagItem[] */
        $children = $tagItem->getChildren();
        $noChildren = empty($children);
        $noContent = true;
        $itsTagOrComponent = $tagItem->Type->Name == TagItemType::Tag
            || $tagItem->Type->Name == TagItemType::Component;

        if ($tagItem->Type->Name == TagItemType::Tag) {
            $html .= '<' . $tagItem->Content;
        }

        if ($tagItem->Type->Name == TagItemType::Component) {
            $html .= "<$replaceByTag data-component=\"{$tagItem->Content}\"";
        }

        if ($tagItem->Type->Name == TagItemType::TextContent) {
            $html .= $tagItem->Content;
        }

        if ($tagItem->Type->Name == TagItemType::Attribute) {
            $html .= ' ' . $tagItem->Content . ($noChildren
                ? ''
                : '="');
        }

        if ($tagItem->Type->Name == TagItemType::AttributeValue) {
            $html .= htmlentities($tagItem->Content);
        }

        if (!$noChildren) {
            foreach ($children as &$childTag) {
                if (
                    $childTag->Type->Name == TagItemType::TextContent
                    || $childTag->Type->Name == TagItemType::Tag
                ) {
                    if ($noContent) {
                        $noContent = false;
                        if ($itsTagOrComponent) {
                            $html .= '>';
                        }
                    }
                }
                $this->buildTag($childTag, $html);
            }
        }
        if ($tagItem->Type->Name == TagItemType::Attribute) {
            $html .= ($noChildren ? '' : '"');
        }

        if ($tagItem->Type->Name == TagItemType::Tag) {
            if ($noContent) {
                $html .= '/>';
            } else {
                $html .= '</' . $tagItem->Content . '>';
            }
        } else if ($tagItem->Type->Name == TagItemType::Component) {
            if ($noContent) {
                $html .= '>';
            }
            //render child component, TODO: replace by script
            //$component = $this->templates[$tagItem->Content];
            //$this->buildInternal($component, $html);
            $html .= "_<[||{$tagItem->Content}||]>_";
            $html .= "</$replaceByTag>";
        }

        // if ($tagItem->Type->Name == TagItemType::Expression) {
        //     $html .= "_(||{$tagItem->Content}||)_";
        // }
    }

    function debug($any)
    {
        echo '<pre>';
        print_r($any);
        echo '</pre>';
    }

    function getDirContents($dir, &$results = array())
    {
        $files = scandir($dir);

        foreach ($files as $key => $value) {
            $path = realpath($dir . DIRECTORY_SEPARATOR . $value);
            if (!is_dir($path)) {
                $results[$path] = true;
            } else if ($value != "." && $value != "..") {
                $this->getDirContents($path, $results);
            }
        }

        return $results;
    }
}
